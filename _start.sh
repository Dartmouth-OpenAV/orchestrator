#!/bin/bash
echo "> /data"
[ -d /data ] || mkdir /data
chown www-data:www-data /data
chmod 770 /data

echo "> sqlite"
# web calls
sqlite3 /dev/shm/web_calls.db << EOF
CREATE TABLE data (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  request_url TEXT,
  request_method TEXT,
  request_headers TEXT,
  request_body TEXT,
  response_code TEXT,
  response_body TEXT,
  added_timestamp TEXT DEFAULT (datetime('now', 'localtime')),
  last_inquiry TEXT DEFAULT (datetime('now', 'localtime')),
  keep_refreshed TEXT DEFAULT 'false',
  refresh_every_x_minutes INTEGER DEFAULT 1,
  last_refresh TEXT
);
CREATE UNIQUE INDEX idx_calls_get_data ON data(request_url,request_method,request_headers,request_body);
CREATE INDEX idx_calls_which_to_process ON data(keep_refreshed);
EOF
chmod 660 /dev/shm/web_calls.db
chown root:www-data /dev/shm/web_calls.db

# errors
sqlite3 /dev/shm/errors.db << EOF
CREATE TABLE data (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  message TEXT,
  code TEXT,
  severity INT,
  tags TEXT,
  source TEXT,
  system TEXT,
  is_reported INTEGER NOT NULL DEFAULT 0,
  time_stamp TEXT DEFAULT (datetime('now', 'localtime'))
);
CREATE INDEX idx_code ON data(code);
CREATE INDEX idx_tags ON data(tags);
CREATE INDEX idx_source ON data(source);
CREATE INDEX idx_system ON data(system);
EOF
chmod 660 /dev/shm/errors.db
chown root:www-data /dev/shm/errors.db

# microservices
sqlite3 /dev/shm/microservices.db << EOF
CREATE TABLE data (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  microservice TEXT,
  device TEXT,
  time_stamp TEXT DEFAULT (datetime('now', 'localtime'))
);
CREATE UNIQUE INDEX idx_microservice_device ON data(microservice,device);
EOF
chmod 660 /dev/shm/microservices.db
chown root:www-data /dev/shm/microservices.db

echo "> web calls"
nohup bash -c "php /var/www/html/include/web_calls.php > /var/log/web_calls.log" &

echo "> microservice error gathering"
nohup bash -c "php /var/www/html/api.php cli_gather_microservice_errors > /var/log/cli_gather_microservice_errors.log" &

echo "> memcached"
nohup bash -c "memcached /usr/bin/memcached -m 64 -p 11211 -u memcache -l 127.0.0.1" &

echo "> apache"
apachectl -D FOREGROUND