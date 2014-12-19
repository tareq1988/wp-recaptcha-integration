CWDNAME=${PWD##*/}

if [ "$CWDNAME" == "trunk" ]; then
	parent=${PWD%/*}
	CWDNAME=${parent##*/}
	ZIPNAME=../../$CWDNAME.zip
else 
	ZIPNAME=../$CWDNAME.zip
fi

find . -path '*/.*' -prune -o -type f \( ! -iname 'README.md'  ! -iname 'mkzip.sh' \) -print | zip $ZIPNAME -@