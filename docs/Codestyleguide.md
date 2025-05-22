# Code Style Guide für die JoomGallery 4.x by JoomGalleryfriends
Letzte Änderungen: 24.02.2025
[Change to the english version](Codestyleguide_en.md)

## Allgemeines

- Encoding: UTF-8 ohne BOM
- Zeilenumbruch: LF (UNIX Style) nicht CR+LF (Windows) und nicht CR (Apple)
- Einrückungen: Pro Stufe 2 Leerzeichen (keine Tabs)
- Keine leere Zeile am Ende der Datei

### Dateiaufbau
1. Datei-Header
2. Namespace definition
3. \defined('_JEXEC') or die;
4. use Befehle (einbinden von notwendigen Klassen)
5. Inhalt

### Datei-Header
2. Komponentenname
3. Author
4. Copyright
5. Lizenz

- PHP Dateien im src-Ordner mit Namespaces versehen!
```php
namespace Joomgallery\Component\Joomgallery\<Client>\<Folder>;
```

- Einbinden weiterer Dateien wenn immer möglich per use (namespacing) oder require_once falls Dateien keinen Namespace haben. KEIN include, include_once oder require.

- Funktionsnamen: Kleiner Anfangsbuchstabe, dann mit jedem neuen 'Wort' ein großer Buchstabe
```php
buildCategoryQuery()
```

- Variablennamen: Kleiner Anfangsbuchstabe, dann mit jedem neuen 'Wort' ein großer Buchstabe
- Schlüsselwörter: Kleingeschrieben (if, while, for, foreach, require_once, true, false, null, function...)
- HTML Ausgabe wenn möglich nur in den Dateien der 'tmpl'-Ordner. Falls HTML in anderen Dateien aufgebaut wird, nicht direkt ausgeben sondern zwischenspeichern und an die Template-Dateien übergeben.
- In den Dateien ausnahmslos Englisch verwenden (Funktionsnamen, Variablennamen, Kommentare, ...)

### Sprache
- 'image' statt 'picture'
- 'sub-category' statt 'subcategory'
- 'current'/'cur' statt 'actual'/'act'

### Leerzeichen
- Vor und nach Gleichheitszeichen IMMER jeweils ein Leerzeichen
- Bei Gruppierungen Gleichheitszeichen mit Hilfe von Leerzeichen untereinander anordnen

#### RICHTIG
```php
$image->orig_width  = $orig_info[0];
$image->orig_height = $orig_info[1];
$image->img_width   = $img_info[0];
$image->img_height  = $img_info[1];
```

#### FALSCH
```php
$image->orig_width=$orig_info[0];
$image->orig_height=$orig_info[1];
$image->img_width=$img_info[0];
$image->img_height=$img_info[1];
```

- Vor und nach PHP-Tags immer mindestens ein Leerzeichen (jeweils auf der Seite des Fragezeichens), auch Strichpunkte dort nicht vergessen

#### RICHTIG
```php
<?php echo $list['owner']; ?>
```

#### FALSCH
```php
<?php echo $list['owner']?>
```

## PHP-Konstrukte (if, while, for, foreach, switch, ...)

- Geschweifte Klammern IMMER verwenden, selbst wenn nur eine Anweisung oder ein weiteres Schlüsselwort folgt
- Geschweifte Klammern stehen IMMER in einer eigenen Zeile

#### RICHTIG
```php
if($integer == 1)
{
  $count = 2;
}
else
{
  if($integer == 2)
  {
    $count = 3;
  }
}
```

#### FALSCH
```php
if($integer == 1){
  $count = 2;
}
else if($integer == 2)
    $count = 3;
```

### switch (Einige Dinge sind hier besonders zu beachten)
- Default muss IMMER erscheinen, auch wenn dieser Fall dann nur das break enthält
- Falls ein break mit Absicht weggelassen wird, muss das durch einen Kommentar deutlich gemacht werden

#### Beispiel
```php
switch($count)
{
  case 1:
    $integer = 5;
    break;
  case 2:
    $integer = 3;
    break;
  case 3:
    $count   = 0;
    // 'break' intentionally omitted
  case 4:
    $integer = 4;
    break;
  default:
    $integer = 0;
    break;
}
```

## Operatoren
- immer '&&' statt 'AND'/'and' und '||' statt 'OR'/'or' verwenden

### Sonderfall - Abfragen mit ? in einer Zeile
```php
$available = $count ? true : false;
```

## Leerzeichen
- Zwischen Schlüsselwort und öffnender runder Klammer KEIN Leerzeichen
- Nach öffnender runder Klammer KEIN Leerzeichen
    (Ausnahme: Es befinden sich komplexe und mehrzeilige Bedingungen innerhalb der runden Klammern)
- Zwischen Variablen und Operatoren jeweils ein Leerzeichen einfügen
- Zischen letzter Bedingung und schließender runder Klammer KEIN Leerzeichen

#### RICHTIG
```php
if($test == true && $integer == 3)
{
  // Anweisungen
}

foreach($array as $key => $integer)
{
  // Anweisungen
}
```

#### FALSCH
```php
if ( $test == true AND $integer == 3 )
{
  // Anweisungen
}
foreach ($array as $key=>$integer)
{
  // Anweisungen
}
```

#### Richtiges Beispiel für mehrzeilige Bedingungen
```php
if(  (  (   ($this->_config->get('jg_showdetailfavourite') == 0 && $this->_user->get('aid') < 1)
          || ($this->_config->get('jg_showdetailfavourite') == 1 && $this->_user->get('aid') < 2)
        )
      ^ ($this->_config->get('jg_usefavouritesforpubliczip') == 1 && $this->_user->id < 1)
      )
    || $this->_config->get('jg_favourites') == 0
  )
{
  // Anweisungen
}
```

## Funktionen

### Leerzeichen
- Zwischen Funktionsnamen und öffnender runder Klammer KEIN Leerzeichen
- Zwischen öffnender runder Klammer und erstem Parameter KEIN Leerzeichen
- Zwischen Komma und nächstem Parameter Leerzeichen einfügen
- Zischen letztem Parameter und schließender runder Klammer KEIN Leerzeichen
    (Falls kein Parameter angegeben wird, öffnende und schließende runde Klammern direkt hintereinander)

#### RICHTIG
```php
$data = getData($count, $integer, $string);
```

#### FALSCH
```php
$data = getData ( $count,$integer, $string );
```

### Leerzeichen bei Funktionsdeklarationen
- Bei Default-Werten zusätzlich Leerzeichen jeweils vor und nach dem Gleichheitszeichen

#### RICHTIG
```php
function getData($count, $integer = 0, $string = '')
{
  // Anweisungen
}
```

#### FALSCH
```php
function getData($count,$integer=0,$string='')
{
  // Anweisungen
}
```

### Leerzeilen
- Werden verwendet um Anweisungblöcke zu gruppieren
- Nach if-Konstrukten und Schleifen grundsätzlich eine Leerzeile
    (Ausnahme: Es folgt eine Anweisung die absolut direkt etwas mit dem Block davor zu tun hat oder es folgt ein weiteres Konstrukt, das eine ähnliche Abfrage durchführt und es somit ebenfalls in direktem Zusammenhang steht.
- Vor einem 'return' grundsätzlich eine Leerzeile einfügen
    (Einzige Ausnahme: Das 'return' ist die einzige Anweisung zwischen zwei geschweiften Klammern)

#### RICHTIG
```php
function getPlural($count)
{
  if($count > 1)
  {
    return true;
  }

  $this->counter--;

  return false;
}
```

#### FALSCH
```php
function getPlural($count)
{
  if($count > 1)
  {

    return true;
  }
  $this->counter--;
  return false;
}
```

## Datenbankabfragen

- MySQL-Schlüsselwörter in Blockbuchstaben
- Übersichtlicher mehrzeiliger Aufbau
- Alle Tabellennamen der Galerie aus den zugehörigen Konstanten verwenden
- Methoden quateName() und quote() verwenden zum Aufbau der Statements

#### Beispiel
```php
$db    = Factory::getDBO();
$query = $db->getQuery(true)
      ->select($db->quoteName(array('a.id', 'a.title')))
      ->from($db->quoteName(_JOOM_TABLE_IMAGES, 'a'))
      ->where($db->quoteName('a.published') . ' = ' . $db->quote(1))
      ->where($db->quoteName('a.approved') . ' = ' . $db->quote(1))
      ->leftJoin($db->quoteName(_JOOM_TABLE_CATEGORIES, 'b') . ' ON ' . $db->quoteName('a.catid') . ' = ' . $db->quoteName('b.cid'))
      ->where($db->quoteName('b.published') . ' = ' . $db->quote(1))
      ->order($db->quoteName('a.ordering') . ' DESC');
$db->setQuery($query);
```

## Template-Dateien

- In den Template-Dateien alternative Syntax für PHP-Konstrukte verwenden

#### RICHTIG
```php
<?php if($this->params->get('show_title')): ?>
  <?php echo $this->params->get('title'); ?>
<?php endif;
```

#### FALSCH
```php
<?php if($this->params->get('show_title'))
  { ?>
  <?php echo $this->params->get('title'); ?>
<?php }
```

- In den Template-Dateien gibt es zwei verschiedene Positionen für die PHP-Tags:
  1. Die PHP-Tags umschließen PHP-Konstrukte wie zum Beispiel if oder foreach.
  2. Die PHP-Tags umschließen einen Output, der im HTML an eine bestimmte Position gesetzt werden muss.

  In beiden Fällen sind die öffnenden PHP-Tags so weit eingerückt, wie wenn sie HTML-Tags wären,
  die sich in die HTML-Struktur einpflegen.
  Schließende PHP-Tags stehen immer am Ende der jeweiligen Zeile mit einem Leerzeichen Abstand.

#### RICHTIG
```php
<?php if($this->params->get('show_testdata')): ?>
  <div class="jg_test">
    <div class="sectiontableheader">
      <h4>
        <?php echo JText::_('JGS_DATA'); ?>
      </h4>
    </div>

    <?php if(!empty($this->slider)): ?>
      <div class="slider">
    <?php endif; ?>

      <?php echo $this->testdata.'&nbsp;'; ?>

    <?php if(!empty($this->slider)): ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
```

#### FALSCH
```php
<?php if($this->params->get('show_testdata')): ?>
  <div class="jg_test">
    <div class="sectiontableheader">
      <h4>
<?php   echo JText::_('JGS_DATA'); ?>
      </h4>
    </div>
<?php   if(!empty($this->slider)): ?>
    <div class="slider">
<?php   endif;
        echo $this->testdata.'&nbsp;';
        if(!empty($this->slider)): ?>
    </div>
<?php   endif; ?>
  </div>
<?php endif; ?>
```

### Klassen
- Immer über der Klassendeklaration einen Kommentarheader im JAVADOC Style
- Beschreibung der Klasse mit Großbuchstabe beginnen
- Notwendige @:
  - package ('JoomGallery'),
  - since (Version, in der die Klasse hinzugefügt wurde)
  (in dieser Reihenfolge)
- Auf die richtige Struktur der Sternchen und @ achten
- Bei Service-Klassen: Immer eine Schnittstelle definieren und implementieren

#### Beispiel
```php
/**
* JoomGallery Refresher Helper
*
* Provides handling with the filesystem where the image files are stored
*
* @package JoomGallery
* @since   4.0.0
*/
class Refresher implements RefresherInterface
{
  // Inhalt
}
```

- Bei statischen Klassen zusätzlich ein @static als erstes @

#### Beispiel
```php
/**
* JoomGallery Helper for the Backend
*
* @static
* @package JoomGallery
* @since  4.0.0
*/
class JoomHelper
{
  // Inhalt
}
```

### Funktionen
- Immer über der Deklaration einen Kommentarheader im JAVADOC Style
- Beschreibung der Funktion mit Großbuchstabe beginnen
- Notwendige @:
  - param   (für jeden Parameter einmal, kann also auch gar nicht vorkommen)
  - return  (bei keinem Rückgabewert '@return  void')
  - since   (Version, in der die Klasse hinzugefügt wurde)
- Zusätzliche @:
  - deprecated  ('as of version versionnumer', als letztes @ einzufügen)
- Zusätzliche Regel für @param:
    Zuerst der Variablentyp, dann der Variablenname, dann eine kurze Beschreibung (mit Grossbuchstabe beginnen)
- Zusätzliche Regel für @return:
    Zuerst der Variablentyp, dann eine kurze Beschreibung (mit Grossbuchstabe beginnen)
    Hinweis: Die Beschreibung des return Wertes beginnt somit also immer genau unter den Variablennamen der Parameter
- Auf die Anordnung achten
- Bei Service-Methoden: Immer eine Schnittstelle definieren und implementieren

#### RICHTIG
```php
/**
 * Collect information for the watermarking
 * (information: dimensions, type, position)
 *
 * @param   array   $imginfo        array with image information of the background image
 * @param   int     $position       Positioning of the watermark
 * @param   int     $resize         resize watermark (0:no,1:by height,2:by width)
 * @param   float   $new_size       new size of the resized watermark in percent related to the file (1-100)
 *
 * @return  array   array with watermark positions; array(x,y)
 *
 * @since   3.6.0
 */
protected function getWatermarkingInfo($imginfo, $position, $resize, $new_size): array
{
  // Anweisungen
}
```

#### FALSCH
```php
/**
 * Moves an image to another folder
 *
 * @param $src string Absolute path to source file
 * @param $dest string Absolute path to destination file
 * @return result boolean True on success, false otherwise
 * @since 1.0.0
 * @deprecated as of version 1.5.0
 */
function copyImage($src, $dest)
{
  // Anweisungen
}
```

### Kommentare innerhalb von Funktionen
- Beginn mit zwei Slashes (keine #)
- Leerzeichen nach den Slashes
- Dann Text mit Großbuchstabe beginnen (meist im Imperativ)

#### RICHTIG
```php
// Perform the request task
$controller->execute(Factory::getApplication()->input->get('task', 'display', 'cmd'));
```

#### FALSCH
```php
//perform the request task
$controller->execute(Factory::getApplication()->input->get('task', 'display', 'cmd'));
```

- Bei Unklarheiten/Diskussionsbedarf hinsichtlich eines Codes diesen mit '// TODO Name: ' kommentieren. Dabei bezeichnet Name nicht den Entwickler, der sich zwingend um das Problem kümmern muss, sondern den 'Entdecker'

## Verschiedenes
- require_once ist keine Funktion -> keine Klammern
- Bei zusammengesetzten Ausdrücken keine Leerzeichen vor und nach den Punkten

#### RICHTIG
```php
require_once JPATH_COMPONENT.DS.'helpers'.DS.'messenger.php';
```

#### FALSCH
```php
require_once(JPATH_COMPONENT . DS . 'helpers'.DS.'messenger.php');
```

- Möglichst immer nur einfache Anführungszeichen verwenden (Performance)

#### RICHTIG
```php
$string = '('.$count.')';
```

#### FALSCH
```php
$string = "($count)";
```
