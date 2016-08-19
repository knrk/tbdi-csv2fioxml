<?php
require_once "header.php";
?>
    <div class="mdl-layout__container layout">
      <form action="process.php" method="POST" enctype="multipart/form-data">
        <div class="upload-card mdl-card mdl-shadow--2dp fade-in">
          <div class="mdl-card__title">
            <div class="mdl-card__title_bg">
              <img src="assets/finance.png"/>
            </div>
            <h2 class="mdl-card__title-text">Konverze CSV do FIO XML</h2>
          </div>
          <div class="mdl-card__supporting-text mdl-card--expand">
            Nahraj správně strukturovaný CSV dokument. <br>Pokud nevíš, použij tento <a href="assets/priklad.csv" target="_blank">vzor</a>.
            <div class="mdl-uploadfield"><input class="mdl-uploadfield__input" id="csvfile" type="file" name="csvfilename" /></div>
          </div>
          <div class="mdl-card__actions mdl-card--border">
            <button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect" name="submit" type="submit">Generuj FIO XML</button>
          </div>
        </div>
      </form>
    </div>
<?php
require_once "footer.php";
?>
