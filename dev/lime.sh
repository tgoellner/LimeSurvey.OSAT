#!/bin/bash

SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
SRCDIR=$( cd "$( dirname "$SCRIPT_DIR/limesurvey_source/application/" )" && pwd )"/application";
DSTDIR=$( cd "$( dirname "$SCRIPT_DIR/../application/" )" && pwd )"/application";

# get all files in dev/application/ to be copied to apllication/ folder

files=$(find $SRCDIR -type f)
for file in $files; do

  newfile="$file"; # the file to copy
  oldfile=${newfile/$SRCDIR/$DSTDIR}; # the new file...

  if [ -f "$oldfile" ];then
    # destination file exists, let's check if it already has been saved to *.lime.php
    renamedoldfile=$(echo $oldfile | sed -E "s/\.php$/\.lime\.php/g");

    if [ ! -f "$renamedoldfile" ];then

      # not yet backuped so let's rename it...
      mv $oldfile $renamedoldfile;
      # and change the class name
      perl -pi -w -e 's/^(\t| {1,})?class /\1class Lime_/g;' $renamedoldfile;
    fi;
  fi;
done

# finally rsync
rsync -a --exclude-from="$SCRIPT_DIR/rsync.exclude" $SRCDIR/ $DSTDIR/

exit;
