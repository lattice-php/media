# Changelog

## 0.1.0 (2026-07-31)


### Features

* a media:conversions command to backfill and regenerate derivatives ([d2aed52](https://github.com/lattice-php/media/commit/d2aed521a7bb54e369cda6cdcf15408c560574b3))
* add id, meta and timestamps to media attachments ([5b57b66](https://github.com/lattice-php/media/commit/5b57b6694c677c099737256f561863ae34759ef9))
* attachment meta and per-item media picker fields ([4b155f1](https://github.com/lattice-php/media/commit/4b155f1348542e7792e7a73179a2566c6f0348b4))
* conversion metadata columns and a configurable media model ([d7423c7](https://github.com/lattice-php/media/commit/d7423c7a07e83d0be3b8724939580bdef385936f))
* image conversions via Illuminate\Image ([415bbc7](https://github.com/lattice-php/media/commit/415bbc7d5312d0895d86cb8bafb3b0340a1055ae))
* **media:** default media policy ([68f2d6a](https://github.com/lattice-php/media/commit/68f2d6a3a28b87ba000bec10db4c383b874cf807))
* **media:** dropzone uploads and detail slideout ([77c974d](https://github.com/lattice-php/media/commit/77c974d11b844755d411850dc99fa925143469e8))
* **media:** HasMedia trait with per-collection sync ([abc1483](https://github.com/lattice-php/media/commit/abc1483acf7fa19b7c01a6844641a25c42ef3253))
* **media:** media and media_attachments schema with models ([ee0cab2](https://github.com/lattice-php/media/commit/ee0cab2bec8e7b433c92511e70c39cb651299e10))
* **media:** media library component and workbench page ([764fcfd](https://github.com/lattice-php/media/commit/764fcfde354d9c99415545490822ab6fad783018))
* **media:** media library table definition ([730a6c2](https://github.com/lattice-php/media/commit/730a6c2c6fe49c33226ddafddf2b478f0264e9d2))
* **media:** media picker form field ([1948b18](https://github.com/lattice-php/media/commit/1948b189be8426d8021b3fc2b4493695b24609ff))
* **media:** media picker renderer ([15a610b](https://github.com/lattice-php/media/commit/15a610b8c17268ede4cf98780cefcec7d1c9e339))
* **media:** react media grid with selection and bulk delete ([3439a4b](https://github.com/lattice-php/media/commit/3439a4bfab87d2b829cb267b8c6726f7e0c15ce5))
* **media:** scaffold lattice-php/media package ([204a332](https://github.com/lattice-php/media/commit/204a3328e9a7eb7f7a43bb51bae4fb58e00cae81))
* **media:** update, delete and bulk delete actions ([1426fc6](https://github.com/lattice-php/media/commit/1426fc692eb67e781fe2908396e3e7a5a23bcde2))
* **media:** upload action for multipart and signed flows ([5f9fa5c](https://github.com/lattice-php/media/commit/5f9fa5cccb8035f872e0545f5198620156cf6b5f))
* per-collection conversions declared on the consuming model ([4b1fb2e](https://github.com/lattice-php/media/commit/4b1fb2e10228bbb356913f4a52c4c5a2c9ba83c2))
* per-instance signed upload and disk configuration ([b76e954](https://github.com/lattice-php/media/commit/b76e9546b41206e996c96b2d9cb32c98b01dad0e))
* per-instance upload validation rules for the media library ([bbb27a7](https://github.com/lattice-php/media/commit/bbb27a77f7b130f9d9e671f228b82aeb5e396830))
* pick-mode composition and client-side max files enforcement ([47e943c](https://github.com/lattice-php/media/commit/47e943c284a6213d445e7c5803f23d6d5a21f215))
* **picker:** render attachment fields per picked item ([b3932fc](https://github.com/lattice-php/media/commit/b3932fc264c622767b800f1d5afac1d86f335e50))
* **picker:** validate and cast per-item attachment fields ([d379d75](https://github.com/lattice-php/media/commit/d379d7508f4a1be60e0cb3008e52fdfe969baa8e))
* preview generated conversions across the library UI ([8277e27](https://github.com/lattice-php/media/commit/8277e275c3e21b3baa91483448ccbe30b07cc813))
* queued image conversions via Illuminate\Image ([42cdd50](https://github.com/lattice-php/media/commit/42cdd502b97ae769e856e91e1e3d7979667805ed))
* require lattice 0.29 and drop the deep-import bridge ([9494f03](https://github.com/lattice-php/media/commit/9494f03ec19cfaf2eb83933078fd61131c277ff9))
* upload feedback, retry and processing states for the library ([15d51e2](https://github.com/lattice-php/media/commit/15d51e2e0d81503bb114f1feb5571db48cf3993a))
* upload UX, per-instance config, picker limits and live S3 coverage ([1989910](https://github.com/lattice-php/media/commit/198991073941f65688c87f0e1d71b8d1d5d0c50c))
* **workbench:** demo app for the media library ([3f2d45d](https://github.com/lattice-php/media/commit/3f2d45dcce97b7b44373cd8cdb7929b6e4b8ebc4))
* **workbench:** demo attachment fields with a gallery caption ([f6042d6](https://github.com/lattice-php/media/commit/f6042d6f778fb1f3688e7d8c72ed1fd34e00031b))
* write picker row meta through syncMedia and add mediaPickerValue ([ffa0634](https://github.com/lattice-php/media/commit/ffa0634212f57a6e8c8e064ba470c80bfa3d12a4))


### Bug Fixes

* allow every pre-1.0 lattice minor from 0.29 up ([10742a2](https://github.com/lattice-php/media/commit/10742a261bb54054536a0e493b291f3066e9af64))
* allow every pre-1.0 lattice minor from 0.29 up ([acb4158](https://github.com/lattice-php/media/commit/acb41585a73633bc089d2e152ef69c0b536c4a84))
* **ci:** rebuild the lockfile with npm 10 to keep the bundled wasm deps ([a1a822d](https://github.com/lattice-php/media/commit/a1a822d71ad680c331e348c5b5873d931e68096a))
* **ci:** regenerate the lockfile with cross-platform optional deps ([01b8a32](https://github.com/lattice-php/media/commit/01b8a326fff6dd04ab413d50ea154ed21caa9282))
* **ci:** regenerate the lockfile with cross-platform optional deps ([f94e8be](https://github.com/lattice-php/media/commit/f94e8be78b6e1bd9000ad9f04b8619f247cc7598))
* **ci:** restore the cross-platform optional deps in the lockfile ([6771465](https://github.com/lattice-php/media/commit/6771465cf1fe28c89cf3838dafb3b5fddfaa2881))
* **media:** default null mime type and size on finalized signed uploads ([74e236f](https://github.com/lattice-php/media/commit/74e236f3c062fe8904a510adfac824e2375edfc2))
* **media:** drop server-side confirm from DeleteMediaAction ([911fe43](https://github.com/lattice-php/media/commit/911fe437a22c4c4536bc56209674c377dce83859))
* **media:** final review fixes ([6731d7b](https://github.com/lattice-php/media/commit/6731d7bf38d8c8e52b71bcb712ef0bc05a31c904))
* **media:** prefix the react translation keys with the lang group ([65c4a36](https://github.com/lattice-php/media/commit/65c4a364dbb2ea0d5aaae4ee2cd9e0c9b5b0fd8a))
* **media:** restore array_values re-indexing in syncMedia ([369e141](https://github.com/lattice-php/media/commit/369e1413ab81f8a53e1c5059cf939885e0b1233d))
* **picker:** keep attachment fields submitting for read-only pickers ([b398cc7](https://github.com/lattice-php/media/commit/b398cc7daad1806acddc897f090ac9de19bedf19))
* **picker:** reindex attachment field lists before schema spread ([c805895](https://github.com/lattice-php/media/commit/c805895c91c4db54c1b0f1c9c0da5c3f5a3d630f))
* record dimensions in the job instead of un-mapping a derivative ([f9a64cd](https://github.com/lattice-php/media/commit/f9a64cda5375cf508a31ce55a48d94592ef91748))
* **tests:** pump the browser server's event loop while retryUntil waits ([a04c1cd](https://github.com/lattice-php/media/commit/a04c1cdaa8ad292cdcc5d516ca43590d1365e665))


### Refactoring

* adopt the shared core upload transport ([f14da56](https://github.com/lattice-php/media/commit/f14da56da00be5f368391a11d76d8f8f30d64d9f))
* drop the superseded isConvertible predicate ([65337b9](https://github.com/lattice-php/media/commit/65337b9ff1d9c0d8645c54802f364424c508c8f0))
* fold the conversion columns into the create migration ([d85f1c5](https://github.com/lattice-php/media/commit/d85f1c50f61ec1186116df1c7c822c7aaae50ad9))
* keep conversion metadata in a single json meta column ([739c58c](https://github.com/lattice-php/media/commit/739c58cb70f73589a496deb9f4810483e69cddea))
* keep the alt text in the media meta payload ([44ff8d9](https://github.com/lattice-php/media/commit/44ff8d9be45fa5fa4f7068447d9616df97ec6139))
* let the media model name its preview conversion ([8e43905](https://github.com/lattice-php/media/commit/8e4390514af600ee4f135268b03954f599726759))
* **media:** apply pr-review cleanups ([263cc6c](https://github.com/lattice-php/media/commit/263cc6cebe8e7cce8f7c8bb5df11ff806c015566))


### Documentation

* conversions, the regenerate command and upload rules ([606997f](https://github.com/lattice-php/media/commit/606997fdf79a93220364e4204968787d6ece90b1))
* name the composer package in the version requirement ([0d38ef9](https://github.com/lattice-php/media/commit/0d38ef9e7383c0ab444c18778151e0ca430f99d3))
