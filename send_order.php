<?
# Sending orders via mail.
#
# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


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
