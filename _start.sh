#!/bin/bash
[ -d /data ] || mkdir /data
chown www-data:www-data /data
chmod 770 /data
nohup bash -c "memcached /usr/bin/memcached -m 64 -p 11211 -u memcache -l 127.0.0.1" &
apachectl -D FOREGROUND