#!/bin/bash

SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
SRCDIR=$( cd "$( dirname "$SCRIPT_DIR/./" )" && pwd );
DSTDIR=$( cd "$( dirname "$SCRIPT_DIR/../" )" && pwd );

# rsync dev to productive
for src in $(find $SRCDIR -type d -iname "osat*");do
    dst=$(echo $src | sed "s/\/dev\//\//");

    # create folders if neccessary
    if [ ! -d "$dst" ];then
        mkdir -p $dst;
    fi;

    # rsync folders
    rsync -a --exclude-from="$SCRIPT_DIR/rsync.exclude" --delete-excluded $src/ $dst/
done;

# rsync template folders
for src in $(find $SRCDIR/templates -type d -iname "osat*");do
    dst=$(echo $src | sed "s/\/dev\/templates\//\/upload\/templates\//");

    # create folders if neccessary
    if [ ! -d "$dst" ];then
        mkdir -p $dst;
    fi;

    # rsync folders
    rsync -a --exclude-from="$SCRIPT_DIR/rsync.exclude" --delete-excluded $src/ $dst/
done;

# clean up tmp folders and caches
rm -rf $SCRIPT_DIR/tmp/assets/*/

exit;
