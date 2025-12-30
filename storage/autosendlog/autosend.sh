#!/bin/bash

### ===== CONFIG =====
LOG_BASE_DIR="/var/www/html/approval_blp/storage/autosendlog"
URL="https://ifca.agungintiland.com/approval_blp/api/autosend"

MAX_RETRY=3
CONNECT_TIMEOUT=10
MAX_TIME=30

LOCKFILE="/tmp/autosend.lock"
CURL="/usr/bin/curl"
DATE="/usr/bin/date"
MKDIR="/usr/bin/mkdir"
RM="/usr/bin/rm"

### ===== LOCK (ANTI DOUBLE RUN) =====
exec 9>"$LOCKFILE" || exit 1
flock -n 9 || exit 0

### ===== LOG DIRECTORY (HARIAN) =====
CURRENT_DAY=$($DATE +"%Y-%m-%d")
LOG_DIR="$LOG_BASE_DIR/$CURRENT_DAY"
$MKDIR -p "$LOG_DIR"

TIMESTAMP=$($DATE "+%Y-%m-%d %H:%M:%S")

SUCCESS_LOG="$LOG_DIR/autosend_success.log"
FAILED_LOG="$LOG_DIR/autosend_failed.log"
RESPONSE_LOG="$LOG_DIR/autosend_response.log"

### ===== EXECUTE WITH RETRY =====
ATTEMPT=1
SUCCESS=false

while [ $ATTEMPT -le $MAX_RETRY ]; do

  RESPONSE_FILE=$(mktemp)

  HTTP_CODE=$($CURL \
    --connect-timeout $CONNECT_TIMEOUT \
    --max-time $MAX_TIME \
    -s \
    -w "%{http_code}" \
    -o "$RESPONSE_FILE" \
    "$URL")

  if [[ "$HTTP_CODE" =~ ^2 ]]; then
    echo "$TIMESTAMP : SUCCESS [$HTTP_CODE] (attempt $ATTEMPT)" >> "$SUCCESS_LOG"
    echo "----- $TIMESTAMP (attempt $ATTEMPT) -----" >> "$RESPONSE_LOG"
    cat "$RESPONSE_FILE" >> "$RESPONSE_LOG"
    echo "" >> "$RESPONSE_LOG"
    SUCCESS=true
    $RM -f "$RESPONSE_FILE"
    break
  else
    echo "$TIMESTAMP : FAILED [$HTTP_CODE] (attempt $ATTEMPT)" >> "$FAILED_LOG"
    echo "----- $TIMESTAMP (attempt $ATTEMPT) HTTP $HTTP_CODE -----" >> "$RESPONSE_LOG"
    cat "$RESPONSE_FILE" >> "$RESPONSE_LOG"
    echo "" >> "$RESPONSE_LOG"
  fi

  $RM -f "$RESPONSE_FILE"
  ATTEMPT=$((ATTEMPT + 1))
  sleep 3

done

### ===== FINAL FAILURE =====
if [ "$SUCCESS" = false ]; then
  echo "$TIMESTAMP : FINAL FAILURE after $MAX_RETRY attempts" >> "$FAILED_LOG"
fi
