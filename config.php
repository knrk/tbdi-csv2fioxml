<?php
const VERSION = '2.0';
const BUILD = '20161209';

const CSV_DELIMITER = ';';
const DESTINATION_ACCOUNT = '2700785944';
const CURRENCY = 'CZK';
const PAYMENT_TYPE = '431022'; // FIO prikaz k inkasu
const PAYMENT_REASON = '380'; // Viz FIO platebni tituly
const CONSTANT_SYMBOL = '0308';

const UPLOAD_TARGET = './uploads/';
const BACKUP_TARGET = './backup/';
const FIO_XML_NAME = 'output';
define('FIO_XML_FILE', FIO_XML_NAME . '.xml');
const FIO_UPLOAD_ENDPOINT = 'https://www.fio.cz/ib_api/rest/import/';
const FIO_TOKEN = '';
?>
