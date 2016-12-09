<?php
require_once(__DIR__ . '/config.php');
require_once('lib/latte/src/latte.php');

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);
ini_set('auto_detect_line_endings', true);

date_default_timezone_set('Europe/Prague');

session_start();

$config = './config.properties';
$props = json_decode(file_get_contents($config), true);

if (!isset($_SESSION['config'])) $_SESSION['config'] = $props;

if (isset($_SESSION['config']['backup']) && $_SESSION['config']['backup']) {
  $files = array_diff(scandir(BACKUP_TARGET), array('.', '..', '.DS_Store'));
  $_SESSION['archives'] = $files;
}

///////

if (isset($_GET['download'])) {

  $file = FIO_XML_FILE;

  header("Content-Description: File Transfer");
  header("Content-Type: application/octet-stream");
  header("Content-Disposition: attachment; filename=\"$file\"");

  readfile ($file);

} else {

  if (isset($_POST['submit'])) {

    // save settings first
    $_SESSION['config']['backup'] = isset($_POST['backup-files']) ? true : false;
    $_SESSION['config']['import'] = isset($_POST['auto-import']) ? true : false;
    file_put_contents($config, json_encode($_SESSION['config']));

    if (file_exists(FIO_XML_FILE)) {
      unlink(FIO_XML_FILE);
    }

    $inputFilename = UPLOAD_TARGET . basename($_FILES['csvfilename']['name']);
    $fileextentsion = pathinfo($inputFilename, PATHINFO_EXTENSION);

    if ($fileextentsion != 'csv') {
      $_SESSION['error'] = 'UNSUPPORTED_FILE';
    } else {
        if (move_uploaded_file($_FILES["csvfilename"]["tmp_name"], $inputFilename)) {
          $_SESSION['status'] = 'OK';
          $_SESSION['uploaded_file'] = basename($_FILES["csvfilename"]["name"]);
        } else {
          $_SESSION['error'] = 'UPLOAD_ERROR';
        }

        // Open csv to read
        $inputFile = fopen($inputFilename, 'rt');

        // Get the headers of the file
        $headers = fgetcsv($inputFile, 0, CSV_DELIMITER);

        // Create a new dom document with pretty formatting
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->formatOutput = true;

        $root = $doc->createElement('Import');
        $root = $doc->appendChild($root);
        $rootXsiAttribute = $doc->createAttribute('xmlns:xsi');
        $rootXsiAttribute->value = 'http://www.w3.org/2001/XMLSchema-instance';
        $root->appendChild($rootXsiAttribute);
        $rootNoNamaspaceSchemeLocationAttribute = $doc->createAttribute('xsi:noNamespaceSchemaLocation');
        $rootNoNamaspaceSchemeLocationAttribute->value = 'http://www.fio.cz/schema/importIB.xsd';
        $root->appendChild($rootNoNamaspaceSchemeLocationAttribute);

          // Add aAttribute root node to the document
          $ordersEl = $doc->createElement('Orders');
          $ordersEl = $root->appendChild($ordersEl);

          // <accountFrom>2207403122</accountFrom>
          // <currency>CZK</currency>
          // <amount>360.25</amount>
          // <accountTo>0000-1181202051</accountTo>
          // <bankCode>3030</bankCode>
          // <vs>112</vs>
          // <date>2016-08-18</date>
          // <messageForRecipient>platba - tel. tarif: 776575179</messageForRecipient>
          // <comment>Lukas Konarik</comment>
          // <paymentReason>380</paymentReason>
          // <paymentType>431022</paymentType>

          // Loop through each row creating a <row> node with the correct data
          while (($row = fgetcsv($inputFile, 0, CSV_DELIMITER)) !== FALSE)
          {
              $transactionEl = $doc->createElement('DomesticTransaction');
              $ordersEl->appendChild($transactionEl);

              $accountFromEl = $doc->createElement('accountFrom');
              $accountFromEl = $transactionEl->appendChild($accountFromEl);
              $accountFromEl->appendChild($doc->createTextNode(DESTINATION_ACCOUNT));

              $currencyEl = $doc->createElement('currency');
              $currencyEl = $transactionEl->appendChild($currencyEl);
              $currencyEl->appendChild($doc->createTextNode(CURRENCY));

              foreach($headers as $i => $header) {
                $values[$header] = $row[$i];
              }

              $amount = bcadd($values['Castka'], 0, 2);
              $amountEl = $doc->createElement('amount');
              $amountEl = $transactionEl->appendChild($amountEl);
              $amountValueEl = $doc->createTextNode($amount);
              $amountEl->appendChild($amountValueEl);

              list($account_number, $account_bank) = explode('/', $values['Inkasovany']);

              $accountToEl = $doc->createElement('accountTo');
              $accountToEl = $transactionEl->appendChild($accountToEl);
              $accountToValue = $doc->createTextNode($account_number);
              $accountToEl->appendChild($accountToValue);

              $accountToBankCodeEl = $doc->createElement('bankCode');
              $accountToBankCodeEl = $transactionEl->appendChild($accountToBankCodeEl);
              $accountToBankCodeValue = $doc->createTextNode($account_bank);
              $accountToBankCodeEl->appendChild($accountToBankCodeValue);

              if (defined(CONSTANT_SYMBOL)) {
                $constantSymbolEl = $doc->createElement('ks');
                $constantSymbolEl = $transactionEl->appendChild($constantSymbolEl);
                $constantSymbolEl->appendChild($doc->createTextNode(CONSTANT_SYMBOL));
              }

              $variableSymbolEl = $doc->createElement('vs');
              $variableSymbolEl = $transactionEl->appendChild($variableSymbolEl);
              $variableSymbolEl->appendChild($doc->createTextNode($values['VariabilniSymbol']));

              if (!empty($values['specificky_symbol'])) {
                $specificSymbolEl = $doc->createElement('ss');
                $specificSymbolEl = $transactionEl->appendChild($specificSymbolEl);
                $specificSymbolEl->appendChild($doc->createTextNode($values['SpecifickySymbol']));
              }

              $date = new DateTime('tomorrow');
              $dueDateEl = $doc->createElement('date');
              $dueDateEl = $transactionEl->appendChild($dueDateEl);
              $dueDateEl->appendChild($doc->createTextNode($date->format('Y-m-d')));

              $messageEl = $doc->createElement('messageForRecipient');
              $messageEl = $transactionEl->appendChild($messageEl);
              $messageEl->appendChild($doc->createTextNode($values['ZpravaProOdesilatele']));

              $noteEl = $doc->createElement('comment');
              $noteEl = $transactionEl->appendChild($noteEl);
              $noteEl->appendChild($doc->createTextNode(iconv('cp1250', 'utf-8', $values['Poznamka'])));

              $paymentReasonEl = $doc->createElement('paymentReason');
              $paymentReasonEl = $transactionEl->appendChild($paymentReasonEl);
              $paymentReasonEl->appendChild($doc->createTextNode(PAYMENT_REASON));

              $paymentTypeEl = $doc->createElement('paymentType');
              $paymentTypeEl = $transactionEl->appendChild($paymentTypeEl);
              $paymentTypeEl->appendChild($doc->createTextNode(PAYMENT_TYPE));

          }

          $strxml = $doc->saveXML();
          $handle = fopen(FIO_XML_FILE, "w");
          fwrite($handle, $strxml);
          fclose($handle);

          if ($_SESSION['config']['backup']) {
            $file = date('m-d') . '.xml';
            copy(FIO_XML_FILE, $file);

            $zip = new ZipArchive();
            $zip->open(BACKUP_TARGET . date('Y') . '.zip', ZipArchive::CREATE);
            $zip->addFile($file);
            $zip->close();

            unlink($file);
          }
      }
  }

  $params = [
      'endpoint' => ENDPOINT,
      'appversion' => VERSION,
      'appbuild' => BUILD,
      'sess' => $_SESSION
  ];

  // RESET

  $_SESSION['status'] = 'INIT';
  unset($_SESSION['uploaded_file']);
  unset($_SESSION['error']);
  unset($_SESSION['archives']);


  // RENDER

  $latte = new Latte\Engine;
  $latte->setTempDirectory('tmp');

  $latte->render('assets/templates/main.latte', $params);
}
?>
