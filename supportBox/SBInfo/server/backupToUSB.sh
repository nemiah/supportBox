#!/usr/bin/env bash

#set -Eeuo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1

DATUM=`date '+%Y-%m-%d'`;
ZEIT=`date '+%H:%M'`;
TEMPFILE="/home/pi/log/backupToUSB-$DATUM.tmp";
echo "" > $TEMPFILE;

echo "INFO: Start $DATUM $ZEIT" >> $TEMPFILE;

/usr/bin/find /home/pi/log/* -maxdepth 1 -mtime +30 -exec rm -r {} \;

echo "INFO: deleted old logs in /home/pi/log/" >> $TEMPFILE;

mount | grep /backup > /dev/null

if [ $? -eq 0 ]; then
        echo "WARNING: /backup already mounted!" >> $TEMPFILE;
	echo "INFO: trying umount /backup" >> $TEMPFILE;
	
	umount /backup

	if [ $? -ne 0 ]; then
        	echo "ERROR: cannot umount /backup!" >> $TEMPFILE;
        	exit 2;
	fi
fi

mount /backup;

mount | grep /backup > /dev/null

if [ $? -ne 0 ]; then
	echo "ERROR: cannot mount /backup!" >> $TEMPFILE;
	exit 2;
fi

echo "INFO: mounted /backup" >> $TEMPFILE;

INPUTDIR="/var/www/html"
OUTPUTDIR="/backup"

if [ ! -d /$OUTPUTDIR/files ]; then
	echo "INFO: creating directories and files" >> $TEMPFILE;

	mkdir /$OUTPUTDIR/files;
	mkdir /$OUTPUTDIR/db;
	date '+%Y-%m-%d' > $OUTPUTDIR/altesDatum;
	umount /backup
	exit 1;
fi

/usr/bin/find $OUTPUTDIR/files/* -maxdepth 0 -mtime +30 -exec rm -r {} \;

echo "INFO: deleted old files in $OUTPUTDIR/files" >> $TEMPFILE;

ADAT=`cat $OUTPUTDIR/altesDatum`;

echo "INFO: Rsync..." >> $TEMPFILE;
rsync -Hrt $INPUTDIR $OUTPUTDIR/files/$DATUM --link-dest=$OUTPUTDIR/files/$ADAT 2>&1 >> $TEMPFILE

if [ $? -ne 0 ]; then
        echo "ERROR: Rsync: $?" >> $TEMPFILE;
        exit 2;
fi


databases=`sudo /usr/bin/mysql -e 'SHOW DATABASES;' | grep -Ev '(Database|information_schema|performance_schema)'`;
databasesLine=`echo $databases | tr "\n" " "`

echo "INFO: backing up $databasesLine" >> $TEMPFILE;

for db in $databases; do
        if [ ! -e $OUTPUTDIR/db/$db ]; then
                mkdir $OUTPUTDIR/db/$db; fi

        /usr/bin/find $OUTPUTDIR/db/$db/* -mtime +30 -exec rm -r {} \; #Keep this one, the general one misses things or does too much

		echo "INFO: deleted old files in $OUTPUTDIR/db/$db/" >> $TEMPFILE;

        sudo /usr/bin/mysqldump --opt --skip-lock-tables --single-transaction --hex-blob --force --ignore-table=mysql.event $db | gzip -9 --best > $OUTPUTDIR/db/$db/$db-$DATUM.sql.gz
done

date '+%Y-%m-%d' > $OUTPUTDIR/altesDatum;



umount /backup

if [ $? -ne 0 ]; then
        echo "ERROR: cannot umount /backup!" >> $TEMPFILE;
        exit 2;
fi

echo "INFO: umounted /backup" >> $TEMPFILE;

ZEIT=`date '+%H:%M'`;
echo "INFO: Done $DATUM $ZEIT" >> $TEMPFILE;

SEKUNDEN=`date '+%s'`;
echo "INFO: Stamp $SEKUNDEN" >> $TEMPFILE;
