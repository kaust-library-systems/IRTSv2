#!/usr/bin/env bash

# Synchronize production code from /home/garcm0b/Work/irts to current directory
# Excludes .git repository from source
# Usage: ./sync-from-production.sh [--dry-run]

SOURCE_DIR="/home/garcm0b/Work/irts/"
DEST_DIR="/home/garcm0b/Work/IRTSv2/"

# Check for dry-run flag
DRY_RUN=""
if [[ "$1" == "--dry-run" || "$1" == "-n" ]]; then
    DRY_RUN="--dry-run"
    echo "DRY RUN MODE - No files will be modified"
fi

echo "Synchronizing from $SOURCE_DIR to $DEST_DIR"
echo "Excluding .git directory..."
echo ""

rsync -av \
  --exclude='.git/' \
  --exclude='.git' \
  $DRY_RUN \
  "$SOURCE_DIR" "$DEST_DIR"

echo ""
if [[ -n "$DRY_RUN" ]]; then
    echo "Dry run complete! Run without --dry-run to actually sync files."
else
    echo "Synchronization complete!"
fi
