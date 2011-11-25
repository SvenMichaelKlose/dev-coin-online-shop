<?
  # $Id: lang_de.inc,v 1.25 2001/12/01 16:31:46 sven Exp $
  #
  # Language definition (D)
  #
  # (c) 2000-2001 dev/consulting GmbH
  #		  Sven Klose (sven@devcon.net)
  #
  # This program is free software; you can redistribute it and/or modify
  # it under the terms of the GNU General Public License as published by
  # the Free Software Foundation; either version 2 of the License, or
  # (at your option) any later version.
  #
  # This program is distributed in the hope that it will be useful,
  # but WITHOUT ANY WARRANTY; without even the implied warranty of
  # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  # GNU General Public License for more details.
  #
  # You should have received a copy of the GNU General Public License
  # along with this program; if not, write to the Free Software
  # Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

  # Words and phrases
  $lang['yes'] = 'ja';
  $lang['no'] = 'nein';
  $lang['root category'] = 'Wurzelkategorie';
  $lang['category'] = 'Kategorie';
  $lang['product group'] = 'Produktgruppe';
  $lang['product group name'] = 'Produktgruppenname';
  $lang['product'] = 'Produkt';
  $lang['local'] = 'lokal';
  $lang['public'] = '&ouml;ffentlich';
  $lang['download'] = 'Download';
  $lang['upload'] = 'Upload';
  $lang['object'] = 'Objekt';
  $lang['objects'] = 'Objekte';
  $lang['class'] = 'Klasse';
  $lang['class name'] = 'Klassenname';
  $lang['user defined classes'] = 'Benutzerdefinierte Klassen';
  $lang['documents'] = 'Dokumente';
  $lang['configuration'] = 'Konfiguration';
  $lang['description'] = 'Beschreibung';
  $lang['unnamed'] = 'unbenannt';
  $lang['mime type'] = 'MIME-Typ';
  $lang['start time'] = 'Startzeit';
  $lang['end time'] = 'Endzeit';
  $lang['remove'] = 'l&ouml;schen';
  $lang['user defined'] = 'benutzerdefiniert';
  $lang['illegal name'] = 'illegaler Name';
  $lang['inherited'] = 'geerbt';
  $lang['new'] = 'neu';
  $lang['tags/info'] = 'Tags/Info';
  $lang['status'] = 'Status';
  $lang['active'] = 'aktiv';
  $lang['previous'] = 'vorherige';
  $lang['next'] = 'n&auml;chste';
  $lang['x of y'] = '%s von %s';
  $lang['product key'] = 'Bestell-Nr.';
  $lang['administration'] = 'Administration';
  $lang['images'] = 'Graphiken';
  $lang['subdirectories'] = 'Unterverzeichnisse';
  $lang['undefined'] = 'undefiniert';
  $lang['available objects'] = 'Verf&uuml;gbare Objekte';
  $lang['current'] = 'aktuell';
  $lang['copy to current'] = 'Kopie an aktueller Position erstellen';
  $lang['price'] = 'Preis';
  $lang['empty'] = 'leer';
  $lang['search'] = 'Suche';

  # Titles
  $lang['title edit_data'] = 'Objekt bearbeiten';
  $lang['title edit_class'] = 'Objektklasse bearbeiten';
  $lang['title defaultview'] = '&Uuml;bersicht der Kategorien';
  $lang['title edit_category'] = 'Kategorie bearbeiten';
  $lang['title view_pages'] = '&Uuml;bersicht Produktgruppen';
  $lang['title edit_page'] = 'Produktgruppe bearbeiten';
  $lang['title view_products'] = '&Uuml;bersicht Produkte';
  $lang['title edit_product'] = 'Produkt bearbeiten';
  $lang['title view_objects'] = 'Objektzuweisungen';
  $lang['title view_classes'] = '&Uuml;bersicht der Objektklassen';
  $lang['title database_menu'] = 'Datenbankoptionen';
  $lang['title edit_duty_fields'] = 'Pflichtfelder im Bestellformular';
  $lang['title edit_user_fields'] = 'Benutzerdefinierte Formularfelder';
  $lang['title shipto'] = 'Lieferadresse';
  $lang['title receiptto'] = 'Lieferscheinadresse';
  $lang['title billto'] = 'Rechnungsadresse';

  # Commands
  $lang['cmd defaultview'] = '&Uuml;bersicht der Kategorien';
  $lang['cmd back/quit'] = 'zur&uuml;ck/verwerfen';
  $lang['cmd create class'] = 'Neue Klasse erstellen';
  $lang['cmd view_objects'] = 'Objekte';
  $lang['cmd view_classes'] = 'Objektklassen';
  $lang['cmd create_tables'] = 'Tabellen erstellen';
  $lang['cmd move_category'] = 'Kategorie verschieben';
  $lang['cmd move further'] = 'weiter verschieben';
  $lang['cmd edit_page'] = 'Produktgruppe bearbeiten';
  $lang['cmd view_products'] = 'Produkte';
  $lang['cmd create_page'] = 'Neue Produktgruppe erstellen';
  $lang['cmd create_product'] = 'Neues Produkt erstellen';
  $lang['cmd edit_category'] = 'edit';
  $lang['cmd database_menu'] = 'Datenbankoptionen';
  $lang['cmd db_consistency_check'] = 'Datenbank optimieren';
  $lang['cmd logout'] = 'Logout';
  $lang['cmd objectbox hide'] = 'Objekte (aus)';
  $lang['cmd objectbox unhide'] = 'Objekte zeigen';
  $lang['cmd user_field_new'] = 'Neues Formularfeld erstellen';
  $lang['cmd db_sort'] = 'Verzeichnisse alphabetisch sortieren';

  # Classes
  $lang['class l_index'] = 'Eingangsseite';
  $lang['class l_category'] = 'Kategorie';
  $lang['class l_page'] = 'Produktgruppe';
  $lang['class l_product'] = 'Produkt';
  $lang['class l_cart'] = 'Warenkorb';
  $lang['class l_empty_cart'] = 'leerer Warenkorb';
  $lang['class l_ecml'] = 'Erfolgreiche Bestellungsannahme';
  $lang['class l_order'] = 'Bestellformular';
  $lang['class l_order_confirm'] = 'Best&auml;tigungsmail an Kunden';
  $lang['class l_order_email'] = 'Email-Bestellung';
  $lang['class l_search'] = 'Suchformular';
  $lang['class d_order_address'] = 'Email-Empf&auml;nger';
  $lang['class d_order_duty'] = 'Pflichtfelder im Bestellformular';
  $lang['class d_order_extra'] = 'Zus&auml;tzliche Felder im Bestellformular';
  $lang['class d_order_email_subject'] = 'Betreff der Best&auml;tigungsmails';
  $lang['class u_attribs'] = 'Produktattribute';
  $lang['class u_attrib_mask'] = 'Auswahl von Produktattributen';

  # Questions
  $lang['ask remove object'] = 'Dieses Objekt wirklich entfernen?';
  $lang['ask remove other object'] =
    'Dies is ein Objekt ausserhalb des aktuellen Verzeichnisses. Wirklich ' .
    'entfernen?';

  # Messages
  $lang['msg tables created'] = 'Tabellen erstellt.';
  $lang['msg record created'] = 'Neuer Eintrag erstellt.';
  $lang['msg root category created'] = 'Wurzelkategorie erstellt.';
  $lang['msg root category exists'] = 'Wurzelkategorie existiert bereits.';
  $lang['msg obj assoced'] = 'Objekt der Klasse %s erstellt.';
  $lang['msg obj removed'] = 'Objekt wurde entfernt.';
  $lang['msg choose category to move'] = 'W&auml;hlen Sie die zu verschiebende Kategorie';
  $lang['msg choose dest category'] = 'W&auml;hlen Sie die Zielkategorie';
  $lang['msg category created'] = 'Kategorie erstellt.';
  $lang['msg category moved'] = 'Kategorie wurde verschoben.';
  $lang['msg no product group'] = 'Keine Produktgruppen in dieser Kategorie.';
  $lang['msg no product'] = 'Keine Produkte in dieser Produktgruppe.';
  $lang['msg obj already exists'] = 'Objekt existiert bereits. Entfernen Sie es zuerst.';

  # Error messages
  $lang['err already exists '] = 'existiert bereits.';
  $lang['err category not moved'] = 'Kategorie wurde nicht verschoben.';
?>
