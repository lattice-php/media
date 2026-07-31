<?php
declare(strict_types=1);

namespace Lattice\Media\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Lattice\Media\Jobs\GenerateMediaConversions;
use Lattice\Media\Models\Media;

final class GenerateConversionsCommand extends Command
{
    protected $signature = 'media:conversions
        {--missing : Only queue media that is actually missing work}
        {--only= : Comma-separated conversion names to target instead of all of them}
        {--force : Drop the targeted conversions first so a changed callback is adopted}
        {--id=* : Restrict the run to these media ids}';

    protected $description = 'Queue the media conversion job for the library, backfilling or regenerating derivatives.';

    public function handle(): int
    {
        $names = $this->targetedNames();
        $queued = 0;

        $this->query()->chunkById(100, function (Collection $chunk) use ($names, &$queued): void {
            /** @var Collection<int, Media> $chunk */
            foreach ($chunk as $media) {
                if ($this->option('missing') && ! $this->needsWork($media, $names)) {
                    continue;
                }

                $this->prepare($media, $names);

                GenerateMediaConversions::dispatch($media);
                $queued++;
            }
        });

        $this->components->info("Queued conversions for {$queued} media.");

        return self::SUCCESS;
    }

    /**
     * @return Builder<Media>
     */
    private function query(): Builder
    {
        $builder = Media::modelQuery()->whereIn('mime_type', Media::convertibleMimeTypes());

        /** @var list<string> $ids */
        $ids = (array) $this->option('id');

        return $ids === [] ? $builder : $builder->whereKey($ids);
    }

    /**
     * The conversion names this run is about; empty means all of them.
     *
     * @return list<string>
     */
    private function targetedNames(): array
    {
        $only = $this->option('only');

        if (! is_string($only) || $only === '') {
            return [];
        }

        return array_values(array_filter(array_map(trim(...), explode(',', $only))));
    }

    /**
     * @param  list<string>  $names
     */
    private function needsWork(Media $media, array $names): bool
    {
        $generated = $media->generated_conversions ?? [];
        $wanted = $names === [] ? array_keys($media->defaultConversions()) : $names;

        if (array_diff($wanted, array_keys($generated)) !== []) {
            return true;
        }

        return $media->width === null && $generated !== [];
    }

    /**
     * Clears map entries so the job regenerates them: everything targeted under
     * `--force`, and otherwise a single entry for a media whose map is complete
     * but whose dimensions were never recorded — the job returns before reading
     * the source when nothing is missing, so it needs one gap to reach the probe
     * that fills `width`/`height`.
     *
     * @param  list<string>  $names
     */
    private function prepare(Media $media, array $names): void
    {
        $generated = $media->generated_conversions ?? [];

        if ($generated === []) {
            return;
        }

        $forget = match (true) {
            (bool) $this->option('force') => $names === [] ? array_keys($generated) : $names,
            $media->width === null => [array_key_first($generated)],
            default => [],
        };

        $remaining = array_diff_key($generated, array_flip($forget));

        if (count($remaining) !== count($generated)) {
            $media->update(['generated_conversions' => $remaining]);
        }
    }
}
