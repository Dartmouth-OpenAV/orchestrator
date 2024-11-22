Copyright (C) 2024 Trustees of Dartmouth College

This project is licensed under the terms of the GNU General Public License (GPL), version 3 or later.

For alternative licensing options, please contact the Dartmouth College OpenAV project team.


# Orchestrator

The orchestrator takes a *system configuration* and interprets it into a *state*. It does so by coordinating communication with all the [microservices](https://github.com/orgs/Dartmouth-OpenAV/repositories?q=microservice) referred to in the *system configuration*. The orchestrator's role is to maintain the *state* and provide it to the various agents interacting with it. For example, one such agent could be a website made available on a tablet for people to control the *state* of a room's AV equipment. Another could be a backend script in charge of turning off equipment after hours. All these integrations end up talking to the orchestrator to get or update the *state* of a system.

Here is a simple example of a *system configuration*:

```
{
    "name": "Room 123",
    "power": {
        "value": {
            "set": [
                {
                    "driver": "ghcr.io/dartmouth-openav/microservice-sony-fpd:current/mytv.fqdn.edu/power",
                    "method": "PUT",
                    "body": "\"$on_or_off\"",
                    "headers": ["content-type: application/json"]
                }
            ],
            "set_process": {
                "true" : {"on_or_off": "on" },
                "false": {"on_or_off": "off"}
            },
            "get": [
                "ghcr.io/dartmouth-openav/microservice-sony-fpd:current/mytv.fqdn.edu/power"
            ],
            "get_process": ["on"]
        }
    }
}
```

Here you can see a JSON hierachy, the orchestrator is agnostic to it so you can devise you own structures to be maintained by the orchestrator. One element stands out though: `power.value`, instead of containing a value like `name`, it contains instructions to get that value from a connected Sony TV. These instuctions also define what to do to that Sony TV is the value is updated. These instructions can be more complex, and the main point here is only to show the relation between a *configuration* and a *state*, indeed the resulting *state* from this *configuration* would be (if the TV was off):

```
{
    "name": "Room 123",
    "power": false
}
```

This would be retrieved with the endpoint: `GET /api/systems/{{system}}/state`

And if you wanted to update the state to turn the TV on, you would use the endpoint: `PUT /api/systems/{{system}}/state` with the body:


```
{
    "power": true
}
```

Here you only want to pass the parts of the hierarchy which need to be updated, hence why `name` is missing. But the data structure which represents the *configuration*, the *state*, and an update to the *state* always has the same hierarchy.


# API

Collection available for import: [orchestrator.collection.json](https://github.com/Dartmouth-OpenAV/orchestrator/blob/main/orchestrator.collection.json)


# Environment Variables

`ADDRESS_MICROSERVICES_BY_NAME` when set to `true`, instead of using microservice mapping to get from a microservice name to a microservice ip/fqdn and port, the orchestrator will address it simply by its name with no mapping. This assumes that a lower layer will resolve the name. For example if running in a Docker setup where containers can talk to each other by name.

`DNS_HARD_CACHE` when set to `true`, DNS entries will be preserved on disk as a way to survive beyond reboots. This is meant to optimize resilience in front of outages, but it's not advantageous in all circumstances, and might cause issues with DNS entries changing. DNS entries which were persisted this way are wiped with the API call to "Clear Global Cache".

`SYSTEM_CONFIGURATIONS_GITHUB_TOKEN` when using Github to host system configuration files, one would hope you do so in a private repository :). And so this environment variables serves to pass a token with read permission to that repository. Token based authentication isn't the best you can do here, you need to manage accounts, classic tokens are a liability, fine grained tokens expire. But it's quick to setup.

`SYSTEM_CONFIGURATIONS_GITHUB_APP_INSTALLATION_ID`, `SYSTEM_CONFIGURATIONS_GITHUB_APP_CLIENT_ID` & `SYSTEM_CONFIGURATIONS_GITHUB_APP_PEM` instead of doing token based authentication into your Github reposity containing system configuration files, you can do App based authentication. This is the better method which avoids the shortcomings of tokens, but it's more work to setup.

`SYSTEM_CONFIGURATIONS_VIA_VOLUME` system configuration files can simply be passed by volume, when this is set to `true`, the orchestrator will look for configuration files in `/system_configurations`. This directory needs to be mounted in when instantiating the container.

`SYSTEM_CONFIGURATIONS_INSTANT_REFRESH` only effective when `SYSTEM_CONFIGURATIONS_VIA_VOLUME` is `true`. When set to `true` configuration files are instantaneously applied upon change. This is aggressive on the filesystem and meant only for development and demonstration purposes.

`VERSION` sets what the API call to "Get Version" will return
