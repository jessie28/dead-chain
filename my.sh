#!/bin/bash

/usr/bin/php /data/csv/peptitl.php
echo "peptitl.php `date +%Y%m%d`" >> /data/csv/my.log
sleep 2
/usr/bin/php /data/csv/peptitl_in.php 
echo "peptitl_in.php `date +%Y%m%d`" >> /data/csv/my.log
