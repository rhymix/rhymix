#!/bin/sh

if [ "$TEMP" = "" ]; then
    TEMP="/tmp";
fi

rm -f `find . -name '*~'`
(cd ..; tar cvf $TEMP/phphtmlparser$1.tar phphtmlparser)

gzip $TEMP/phphtmlparser$1.tar
