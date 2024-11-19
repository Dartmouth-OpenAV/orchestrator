Copyright (C) 2024 Trustees of Dartmouth College

This project is licensed under the terms of the GNU General Public License (GPL), version 3 or later.

For alternative licensing options, please contact the Dartmouth College OpenAV project team.

# Orchestrator

The orchestrator takes a system configuration and interprets it into a state. It does so by coordinating communication with all the [microservices](https://github.com/orgs/Dartmouth-OpenAV/repositories?q=microservice) refered to in the system configuration. The orchestrator's role is to maintain the state and prodive it to the various agents interacting with it. For example, one such agent could be a website made available on a tablet for people to control the state of a room's AV equipment. Another could be a backend script in charge of turning off equipment after hours. All these integrations end up taking to the orchestrator to get and update the state of a system.


# API



# Environment Variables
ADDRESS_MICROSERVICES_BY_NAME
DNS_HARD_CACHE
SYSTEM_CONFIGURATIONS_GITHUB_TOKEN
SYSTEM_CONFIGURATIONS_INSTANT_REFRESH
SYSTEM_CONFIGURATIONS_VIA_VOLUME
VERSION
