<?php
/**
 * @package Clearbricks
 *
 * Backwards compatibility with clearbricks < 2.0
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 * @version 1.2
 */

$src = dirname(__FILE__) . '/src';

# Autoload
$__autoload = [
    'l10n'                => $src . '/Common/L10n.php',
    'crypt'               => $src . '/Common/Crypt.php',
    'dt'                  => $src . '/Common/Dt.php',
    'files'               => $src . '/Common/Files.php',
    'path'                => $src . '/Common/Path.php',
    'form'                => $src . '/Common/Form.php',
    'formSelectOption'    => $src . '/Common/FormSelectOption.php',
    'forms'               => $src . '/Common/Forms.php',
    'formsSelectOption'   => $src . '/Common/FormsSelectOption.php',
    'html'                => $src . '/Common/Html.php',
    'http'                => $src . '/Common/Http.php',
    'text'                => $src . '/Common/Text.php',

    'cursor'              => $src . '/Database/Layer/Cursor.php',
    'i_dbLayer'           => $src . '/Database/Layer/InterfaceLayer.php',
    'dbLayer'             => $src . '/Database/Layer/Layer.php',
    'record'              => $src . '/Database/Layer/Record.php',
    'staticRecord'        => $src . '/Database/Layer/StaticRecord.php',

    'mysqliConnection'    => $src . '/Database/Layer/Driver/Mysqli.php',
    'mysqlimb4Connection' => $src . '/Database/Layer/Driver/Mysqlimb4.php',
    'mysqliConnection'    => $src . '/Database/Layer/Driver/Pgsql.php',
    'sqliteConnection'    => $src . '/Database/Layer/Driver/Sqlite.php',

    'dbStruct'            => $src . '/Database/Schema/Struct.php',
    'dbStructTable'       => $src . '/Database/Schema/StructTable.php',
    'i_dbSchema'          => $src . '/Database/Schema/InterfaceSchema.php',
    'dbSchema'            => $src . '/Database/Schema/Schema.php',

    'mysqliSchema'        => $src . '/Database/Schema/Driver/Mysqli.php',
    'mysqlimb4Schema'     => $src . '/Database/Schema/Driver/Mysqlimb4.php',
    'pgsqlSchema'         => $src . '/Database/Schema/Driver/Pgsql.php',
    'sqliteSchema'        => $src . '/Database/Schema/Driver/Sqlite.php',

    'diff'                => $src . '/Diff/Diff.php',
    'tidyDiff'            => $src . '/Diff/TidyDiff.php',
    'tidyDiffChunk'       => $src . '/Diff/TidyDiffChunk.php',
    'tidyDiffLine'        => $src . '/Diff/TidyDiffLine.php',

    'filemanager'         => $src . '/FileManager/FileManager.php',
    'fileItem'            => $src . '/FileManager/FileItem.php',

    'fileZip'             => $src . '/Zip/FileZip.php',
    'fileUnzip'           => $src . '/Zip/FileUnzip.php',

    'htmlFilter'          => $src . '/Html/Filter.php',
    'htmlValidator'       => $src . '/Html/Validator.php',
    'formComponent'       => $src . '/Html/Form/Component.php',
    'formForm'            => $src . '/Html/Form/Form.php',
    'formTextarea'        => $src . '/Html/Form/Textarea.php',
    'formInput'           => $src . '/Html/Form/Input.php',
    'formCheckbox'        => $src . '/Html/Form/Checkbox.php',
    'formColor'           => $src . '/Html/Form/Color.php',
    'formDate'            => $src . '/Html/Form/Date.php',
    'formDatetime'        => $src . '/Html/Form/Datetime.php',
    'formEmail'           => $src . '/Html/Form/Email.php',
    'formFile'            => $src . '/Html/Form/File.php',
    'formHidden'          => $src . '/Html/Form/Hidden.php',
    'formNumber'          => $src . '/Html/Form/Number.php',
    'formPassword'        => $src . '/Html/Form/Password.php',
    'formRadio'           => $src . '/Html/Form/Radio.php',
    'formTime'            => $src . '/Html/Form/Time.php',
    'formUrl'             => $src . '/Html/Form/Url.php',
    'formLabel'           => $src . '/Html/Form/Label.php',
    'formFieldset'        => $src . '/Html/Form/Fieldset.php',
    'formLegend'          => $src . '/Html/Form/Legend.php',
    'formSelect'          => $src . '/Html/Form/Select.php',
    'formOptgroup'        => $src . '/Html/Form/Optgroup.php',
    'formOption'          => $src . '/Html/Form/Option.php',

    'imageMeta'           => $src . '/Image/ImageMeta.php',
    'imageTools'          => $src . '/Image/ImageTools.php',

    'pager'               => $src . '/Pager/Pager.php',

    'sessionDB'           => $src . '/Session/SessionDb.php',

    'wiki2xhtml'          => $src . '/Text/Wiki2xhtml.php',
];

# Feed Reader
$__autoload['feedParser'] = dirname(__FILE__) . '/legacy/net.http.feed/class.feed.parser.php';
$__autoload['feedReader'] = dirname(__FILE__) . '/legacy/net.http.feed/class.feed.reader.php';

# Send Mail Utilities
$__autoload['mail'] = dirname(__FILE__) . '/legacy/mail/class.mail.php';

# Mail Convert and Rewrap
$__autoload['mailConvert'] = dirname(__FILE__) . '/legacy/mail.convert/class.mail.convert.php';

# Send Mail Through Sockets
$__autoload['socketMail'] = dirname(__FILE__) . '/legacy/mail/class.socket.mail.php';

# Mime Message Parser
$__autoload['mimeMessage'] = dirname(__FILE__) . '/legacy/mail.mime/class.mime.message.php';

# REST Server
$__autoload['restServer'] = dirname(__FILE__) . '/legacy/rest/class.rest.php';
$__autoload['xmlTag']     = dirname(__FILE__) . '/legacy/rest/class.rest.php';

# Simple Template Systeme
$__autoload['template']               = dirname(__FILE__) . '/legacy/template/class.template.php';
$__autoload['tplNode']                = dirname(__FILE__) . '/legacy/template/class.tplnode.php';
$__autoload['tplNodeBlock']           = dirname(__FILE__) . '/legacy/template/class.tplnodeblock.php';
$__autoload['tplNodeText']            = dirname(__FILE__) . '/legacy/template/class.tplnodetext.php';
$__autoload['tplNodeValue']           = dirname(__FILE__) . '/legacy/template/class.tplnodevalue.php';
$__autoload['tplNodeBlockDefinition'] = dirname(__FILE__) . '/legacy/template/class.tplnodeblockdef.php';
$__autoload['tplNodeValueParent']     = dirname(__FILE__) . '/legacy/template/class.tplnodevalueparent.php';

# URL Handler
$__autoload['urlHandler'] = dirname(__FILE__) . '/legacy/url.handler/class.url.handler.php';

# SQL Batch on XML Files
$__autoload['xmlsql'] = dirname(__FILE__) . '/legacy/xmlsql/class.xmlsql.php';

# Common Socket Class
$__autoload['netSocket'] = dirname(__FILE__) . '/legacy/net/class.net.socket.php';

# HTTP Client
$__autoload['netHttp']    = dirname(__FILE__) . '/legacy/net.http/class.net.http.php';
$__autoload['HttpClient'] = dirname(__FILE__) . '/legacy/net.http/class.net.http.php';

# NNTP Client
$__autoload['netNntp']     = dirname(__FILE__) . '/legacy/net.nntp/class.net.nntp.php';
$__autoload['nntpMessage'] = dirname(__FILE__) . '/legacy/net.nntp/class.nntp.message.php';

# XML-RPC Client and Server
$__autoload['IXR_Value']               = dirname(__FILE__) . '/legacy/ext/incutio.ixr_library.php';
$__autoload['IXR_Message']             = dirname(__FILE__) . '/legacy/ext/incutio.ixr_library.php';
$__autoload['IXR_Server']              = dirname(__FILE__) . '/legacy/ext/incutio.ixr_library.php';
$__autoload['IXR_Request']             = dirname(__FILE__) . '/legacy/ext/incutio.ixr_library.php';
$__autoload['IXR_Client']              = dirname(__FILE__) . '/legacy/ext/incutio.ixr_library.php';
$__autoload['IXR_Error']               = dirname(__FILE__) . '/legacy/ext/incutio.ixr_library.php';
$__autoload['IXR_Date']                = dirname(__FILE__) . '/legacy/ext/incutio.ixr_library.php';
$__autoload['IXR_Base64']              = dirname(__FILE__) . '/legacy/ext/incutio.ixr_library.php';
$__autoload['IXR_IntrospectionServer'] = dirname(__FILE__) . '/legacy/ext/incutio.ixr_library.php';
$__autoload['IXR_ClientMulticall']     = dirname(__FILE__) . '/legacy/ext/incutio.ixr_library.php';

$__autoload['xmlrpcValue']               = dirname(__FILE__) . '/legacy/net.xmlrpc/class.net.xmlrpc.php';
$__autoload['xmlrpcMessage']             = dirname(__FILE__) . '/legacy/net.xmlrpc/class.net.xmlrpc.php';
$__autoload['xmlrpcRequest']             = dirname(__FILE__) . '/legacy/net.xmlrpc/class.net.xmlrpc.php';
$__autoload['xmlrpcDate']                = dirname(__FILE__) . '/legacy/net.xmlrpc/class.net.xmlrpc.php';
$__autoload['xmlrpcBase64']              = dirname(__FILE__) . '/legacy/net.xmlrpc/class.net.xmlrpc.php';
$__autoload['xmlrpcClient']              = dirname(__FILE__) . '/legacy/net.xmlrpc/class.net.xmlrpc.php';
$__autoload['xmlrpcClientMulticall']     = dirname(__FILE__) . '/legacy/net.xmlrpc/class.net.xmlrpc.php';
$__autoload['xmlrpcServer']              = dirname(__FILE__) . '/legacy/net.xmlrpc/class.net.xmlrpc.php';
$__autoload['xmlrpcIntrospectionServer'] = dirname(__FILE__) . '/legacy/net.xmlrpc/class.net.xmlrpc.php';

# autoload for clearbricks
function cb_autoload($name)
{
    global $__autoload;

    if (isset($__autoload[$name])) {
        require_once $__autoload[$name];
    }
}
spl_autoload_register('cb_autoload');
