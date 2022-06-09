#!/bin/sh

# Source: https://stackoverflow.com/a/28341234/1190086
# https://docs.docker.com/engine/reference/builder/#healthcheck

# Input file
FILE=/var/log/cron.log

# Time in seconds
OLD_TIME=120

# Current epoch time
CUR_TIME=$(date +%s)

# Current file epoch time
FILE_TIME=$(stat $FILE -c %Y)

# How old file is in seconds
TIME_DIFF=$(($CUR_TIME - $FILE_TIME))

# Is file older than 120s?
if [ $TIME_DIFF -gt $OLD_TIME ]; then
  exit 1
else
  exit 0
fi

