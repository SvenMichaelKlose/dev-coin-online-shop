<?
  # $Id: send_order.php,v 1.12 2001/12/01 16:17:18 sven Exp $
  #
  # Sending orders via mail.
  #
  # (c) 2000-2001 dev/consulting GmbH,
  #	     	  Sven Klose (sven@devcon.net)
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

  require 'lib/htmllig2latin.php';

  # Invoke scanner with email template and send the mail.
  function do_order ()
  {
    global $SERVER_NAME, $scanner, $dep, $db;

    # Get context position.
    $table = $scanner->context_table;
    $id = $scanner->context['id'];
    $scanner->push_context ();

    # Send order.
    $from =& cms_fetch_object ('d_order_address');
    if (!$from)
      panic ('No order address specified.');
    $template =& cms_fetch_object ('l_order_email');
    if (!$template)
      panic ('No order template specified.');
    $tree =& $scanner->scan ($template);
    $body = $scanner->exec ($tree, $table, $id);
    mail ($from, "$SERVER_NAME - e-shop", htmllig2latin ($body)); 

    # Send confirmation.
    $subject =& cms_fetch_object ('d_order_email_subject');
    if (!$subject)
      panic ('No order email subject.');
    $to = dirtag_order_shipto (array ('field' => 'email'));
    $template =& cms_fetch_object ('l_order_confirm');
    if (!$template)
      panic ('No order confirmation template.');
    $tree =& $scanner->scan ($template);
    $body = $scanner->exec ($tree, $table, $id);
    mail ($to, $subject, htmllig2latin ($body), "From: $from\nReply-To: $from");

    $scanner->pop_context ();
  }
?>
