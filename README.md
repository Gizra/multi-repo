## Requirements

* [DDEV](https://ddev.readthedocs.io/en/stable/)

## Installation

    git clone --recurse-submodules git@github.com:Gizra/multi-repo.git
    cd multi-repo
    ddev composer install
    cp .ddev/config.local.yaml.example .ddev/config.local.yaml
    ddev restart

Every time you want to re-install:

    ddev restart


Notice that in the end of the `ddev restart` we get a one time admin link to login, to two sites:

1. https://multi-repo.ddev.site/ -- The default site, the one that holds all the "default" config
1. https://basic.ddev.site/ -- A single site, fetched by git-submodule, with config overrides done by config split. It's hosted in https://github.com/Gizra/multi-repo-basic

