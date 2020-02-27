## Requirements

* [DDEV](https://ddev.readthedocs.io/en/stable/)

## Installation

    git clone --recurse-submodules git@github.com:amitaibu/multi-repo.git
    cd multi-repo
    ddev composer install
    cp .ddev/config.local.yaml.example .ddev/config.local.yaml
    ddev restart

Every time you want to re-install:

    ddev restart


Notice that in the end of the `ddev restart` we get a one time admin link to login.

## Sites and Git Submodules

https://github.com/amitaibu/multi-repo-basic

