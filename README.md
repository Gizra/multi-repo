## Requirements

* [DDEV](https://ddev.readthedocs.io/en/stable/)

## Installation

    git clone --recurse-submodules git@github.com:Gizra/multi-repo.git
    cd multi-repo
    ddev composer install
    cp .ddev/config.local.yaml.example .ddev/config.local.yaml
    ddev restart

    # Allow git inside the container to work, with your hosts's credentials.
    # ddev auth ssh

    # Fetch subsites
    ddev exec "cd .. && ./vendor/bin/robo fetch ./robo/sites-collection1.csv" && ddev restart



Every time you want to re-install:

    ddev restart

To re-fetch subsites

    ddev exec "cd .. && ./vendor/bin/robo fetch ./robo/sites-collection1.csv" && ddev restart

Note it is possibly to execute this also from the host, with

    ./vendor/bin/robo fetch ./robo/sites-collection1.csv && ddev restart


To clean the working directory after a re-fetch

    ddev exec "cd .. && ./vendor/bin/robo reset"

Notice that in the end of the `ddev restart` we get a one time admin link to login, to two sites:

1. https://multi-repo.ddev.site/ -- The default site, the one that holds all the "default" config
1. https://basic.ddev.site/ -- A single site, fetched by git-submodule, with config overrides done by config split. It's hosted in https://github.com/Gizra/multi-repo-basic


### Troubleshooting

If you had a previous installation of this repo, and have an error similar to `composer [install] failed, composer command failed: failed to load any docker-compose.*y*l files in /XXX/multi-repo/.ddev: err=<nil>. stderr=`

then execute the following, and re-try installation steps.

    ddev rm --unlist
