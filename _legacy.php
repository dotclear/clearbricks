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

    'imageMeta'              => $src . '/Image/ImageMeta.php',
    'imageTools'             => $src . '/Image/ImageTools.php',

    'netSocket'                 => $src . '/Network/Socket/Socket.php',
    'netSocketIterator'         => $src . '/Network/Socket/Iterator.php',
    'netNntp'                   => $src . '/Network/Nntp/Nntp.php',
    'nntpMessage'               => $src . '/Network/Nntp/Message.php',
    'netHttp'                   => $src . '/Network/Http/Http.php',
    'HttpClient'                => $src . '/Network/Http/Client.php',
    'feedParser'                => $src . '/Network/Feed/Parser.php',
    'feedReader'                => $src . '/Network/Feed/Reader.php',
    'xmlrpcException'           => $src . '/Network/Xmlrpc/Exception.php',
    'xmlrpcValue'               => $src . '/Network/Xmlrpc/Value.php',
    'xmlrpcMessage'             => $src . '/Network/Xmlrpc/Message.php',
    'xmlrpcRequest'             => $src . '/Network/Xmlrpc/Request.php',
    'xmlrpcDate'                => $src . '/Network/Xmlrpc/Date.php',
    'xmlrpcBase64'              => $src . '/Network/Xmlrpc/Base64.php',
    'xmlrpcClient'              => $src . '/Network/Xmlrpc/Client.php',
    'xmlrpcClientMulticall'     => $src . '/Network/Xmlrpc/ClientMulticall.php',
    'xmlrpcServer'              => $src . '/Network/Xmlrpc/Server.php',
    'xmlrpcIntrospectionServer' => $src . '/Network/Xmlrpc/IntrospectionServer.php',

    'pager'                  => $src . '/Pager/Pager.php',

    'sessionDB'              => $src . '/Session/SessionDb.php',

    'template'               => $src . '/Template/Template.php',
    'tplNode'                => $src . '/Template/Node.php',
    'tplNodeBlock'           => $src . '/Template/NodeBlock.php',
    'tplNodeText'            => $src . '/Template/NodeText.php',
    'tplNodeValue'           => $src . '/Template/NodeValue.php',
    'tplNodeBlockDefinition' => $src . '/Template/NodeBlockDefinition.php',
    'tplNodeValueParent'     => $src . '/Template/NodeValueParent.php',

    'wiki2xhtml'             => $src . '/Text/Wiki2xhtml.php',
];

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

# URL Handler
$__autoload['urlHandler'] = dirname(__FILE__) . '/legacy/url.handler/class.url.handler.php';

# SQL Batch on XML Files
$__autoload['xmlsql'] = dirname(__FILE__) . '/legacy/xmlsql/class.xmlsql.php';

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


# autoload for clearbricks
function cb_autoload($name)
{
    global $__autoload;

    if (isset($__autoload[$name])) {
        require_once $__autoload[$name];
    }
}
spl_autoload_register('cb_autoload');
