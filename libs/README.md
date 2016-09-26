# libs
This folder contains private HopTrip libraries. The libraries are
installed as `git subtree` and linked via local composer repo.

## Adding/updating a local dependency
* Add a corresponding library as a remote. E.g. `git remote add lib-api-client https://github.com/travelsense/lib-api-client`
* Merge using git subtree: `git subtree add --prefix libs/api-client lib-api-client 1.0.4 --squash`
* In case of update use `pull` instead of `add`
* Link the folder in composer.json as a "path" repo
* Run `composer update`
