#!/bin/bash
echo "> /data"
[ -d /data ] || mkdir /data
chown www-data:www-data /data
chmod 770 /data

echo "> sqlite"
sqlite3 /dev/shm/web_calls.db << EOF
CREATE TABLE data (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  request_url TEXT,
  request_method TEXT,
  request_headers TEXT,
  request_body TEXT,
  response_code TEXT,
  response_body TEXT,
  added_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_inquiry TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  keep_refreshed TEXT DEFAULT 'false',
  refresh_every_x_minutes INTEGER DEFAULT 1,
  last_refresh TIMESTAMP
);

CREATE UNIQUE INDEX idx_calls_get_data ON data(request_url,request_method,request_headers,request_body);
CREATE INDEX idx_calls_which_to_process ON data(keep_refreshed,in_process);
EOF

chmod 660 /dev/shm/web_calls.db
chown root:www-data /dev/shm/web_calls.db

echo "> web calls"
nohup bash -c "php /var/www/html/include/web_calls.php > /var/log/web_calls.log" &

echo "> memcached"
nohup bash -c "memcached /usr/bin/memcached -m 64 -p 11211 -u memcache -l 127.0.0.1" &

echo "> apache"
apachectl -D FOREGROUND