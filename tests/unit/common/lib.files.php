<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

namespace tests\unit;

require_once __DIR__.'/../bootstrap.php';

require_once CLEARBRICKS_PATH . '/common/lib.l10n.php';
require_once CLEARBRICKS_PATH.'/common/lib.files.php';
require_once CLEARBRICKS_PATH.'/common/lib.text.php';

define('TEST_DIRECTORY', realpath(
    __DIR__ .'/../fixtures/files'
));

use atoum;

/*
 * Test common/lib.files.php
 */
class files extends atoum
{
    /**
     * Scan a directory. For that we use the /../fixtures/files which contains
     * know files
     */
    public function testScanDir()
    {
        // Normal
        $this
            ->array(\files::scandir(TEST_DIRECTORY))
            ->containsValues(array('.', '..', '1-one.txt', '02-two.txt', '30-three.txt'));

        // Sorted
        $this
            ->array(\files::scandir(TEST_DIRECTORY, true))
            ->isIdenticalTo(array('.','..','02-two.txt', '1-one.txt', '30-three.txt'));

        // DOn't exists
        $this
            ->exception(function() {
                \files::scandir('thisdirectorydontexists');
            });
    }

    /**
     * Test the extension
     */
    public function testExtension()
    {
        $this
            ->string(\files::getExtension("fichier.txt"))
            ->isEqualTo('txt');

        $this
            ->string(\files::getExtension('fichier'))
            ->isEqualTo('');
    }

    /**
     * Test the mime type with two well know mimetype
     * Normally if a file type is unknow it must have a application/octet-stream mimetype
     * javascript files might have an application/x-javascript mimetype regarding
     * W3C spec.
     * See http://en.wikipedia.org/wiki/Internet_media_type for all mimetypes
     */
    public function testGetMimeType()
    {
        $this
            ->string(\files::getMimeType('fichier.txt'))
            ->isEqualTo('text/plain');

        $this
            ->string(\files::getMimeType('fichier.css'))
            ->isEqualTo('text/css');

        $this
            ->string(\files::getMimeType('fichier.js'))
            ->isEqualTo('application/javascript');

        // FIXME: SHould be application/octet-stream (default for unknow)
        // See http://www.rfc-editor.org/rfc/rfc2046.txt section 4.
        // This test don't pass
        $this
            ->string(\files::getMimeType('fichier.dummy'))
            ->isEqualTo('application/octet-stream');
    }

    /**
     * There's a lot of mimetypes. Only test if mimetypes array is not empty
     */
    public function testMimeTypes()
    {
        $this
            ->array(\files::mimeTypes())
            ->isNotEmpty();
    }

    /**
     * Try to register a new mimetype: test/test which don't exists
     */
    public function testRegisterMimeType()
    {
        \files::registerMimeTypes(array('text/test'));
        $this
            ->array(\files::mimeTypes())
            ->contains('text/test');
    }

    /**
     * Test if a file is deletable. Under windows every file is deletable
     * TODO: Do it under an Unix/Unix-like system
     */
    public function testFileIsDeletable()
    {
        $tmpname = tempnam(sys_get_temp_dir(), "testfile.txt");
        $file = fopen($tmpname, "w+");
        $this
            ->boolean(\files::isDeletable($tmpname))
            ->isTrue();
        fclose($file);
        unlink($tmpname);
    }

    /**
     * Test if a directory is deletable
     * TODO: Do it under Unix/Unix-like system
     */
    public function testDirIsDeletable()
    {
        $dirname = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "testdirectory";
        mkdir($dirname);
        $this
            ->boolean(\files::isDeletable($dirname))
            ->isTrue();
        rmdir($dirname);
    }

    /**
     * Create a directories structure and delete it
     */
    public function testDeltree()
    {
        $dirstructure = join(DIRECTORY_SEPARATOR, array(".", "temp", "tests", "are", "good", "for", "you"));
        mkdir($dirstructure, 0700, true);
        $this
            ->boolean(\files::deltree("./temp"))
            ->isTrue();

        $this
            ->boolean(is_dir('./temp'))
            ->isFalse();
    }

    /**
     * There's a know bug on windows system with filemtime,
     * so this test might fail within this system
     */
    public function testTouch()
    {
        $file_name = tempnam(sys_get_temp_dir(), "testfile.txt");
        $fts = filemtime($file_name);
        // Must keep at least one second of difference
        sleep(1);
        \files::touch($file_name);
        clearstatcache(); // stats are cached, clear them!
        $sts = filemtime($file_name);
        $this
            ->integer($sts)
            ->isGreaterThan($fts);
    }

    /**
     * Make a single directory
     */
    public function testMakeDir()
    {
        // Test no parent
        $dirPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'atestdirectory';
        \files::makeDir($dirPath);
        $this
            ->boolean(is_dir($dirPath))
            ->isTrue();
        \files::deltree($dirPath);
    }

    /**
     * Make a directory structure
     */
    public function testMakeDirWithParent()
    {
        // Test multitple parent
        $dirPath =  sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'this/is/a/test/directory/';
        \files::makeDir($dirPath, true);
        $path = '';
        foreach(array(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'this','is','a','test','directory') as $p) {
            $path .= $p . DIRECTORY_SEPARATOR;
            $this->boolean(is_dir($path));
        }
        \files::deltree($dirPath);
    }

    /**
     * Try to create an forbidden directory
     * Under windows try to create a reserved directory
     * Under Unix/Unix-like sytem try to create a directory at root dir
     */
    public function testMakeDirImpossible()
    {
        if (DIRECTORY_SEPARATOR == '\\')
        {
            $dir = 'COM1'; // Windows system forbid that name
        } else {
            $dir = '/dummy'; // On Unix system can't create a directory at root
        }

        $this->exception(function() use($dir) {
            \files::makeDir($dir);
        });
    }

    public function testInheritChmod()
    {
        $dirName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'atestdir2';
        $sonDirName = $dirName . DIRECTORY_SEPARATOR . 'anotherDir';
        mkdir($dirName, 0777);
        mkdir($sonDirName);
        $parentPerms = fileperms($dirName);
        \files::inheritChmod($sonDirName);
        $sonPerms = fileperms($sonDirName);
        $this
            ->boolean($sonPerms === $parentPerms)
            ->isTrue();
        \files::deltree($dirName);
    }

    public function testPutContent()
    {
        $content = 'A Content';
        $filename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'atestfile.txt';
        \files::putContent($filename, $content);
        $this
            ->string(file_get_contents($filename))
            ->isEqualTo($content);
        unset($filename);
    }

    public function testSize()
    {
        $this
            ->string(\files::size(512))
            ->isEqualTo('512 B');

        $this
            ->string(\files::size(1024))
            ->isEqualTo('1 KB');

        $this
            ->string(\files::size(1024 + 1024 + 1))
            ->isEqualTo('2 KB');

        $this
            ->string(\files::size(1024 * 1024))
            ->isEqualTo('1 MB');

        $this
            ->string(\files::size(1024 * 1024 *1024))
            ->isEqualTo('1 GB');

        $this
            ->string(\files::size(1024 * 1024 *1024 * 3))
            ->isEqualTo('3 GB');

        $this
            ->string(\files::size(1024 * 1024 * 1024 * 1024))
            ->isEqualTo('1 TB');
    }

    public function testStr2Bytes()
    {
        $this
            ->integer(\files::str2bytes('512B'))
            ->isEqualTo(512);

        $this
            ->integer(\files::str2bytes('512 B'))
            ->isEqualTo(512);

        $this
            ->integer(\files::str2bytes('1k'))
            ->isEqualTo(1024);

        $this
            ->integer(\files::str2bytes('1M'))
            ->isEqualTo(1024*1024);
        // Max int limit reached, we have a float here
        $this
            ->float(\files::str2bytes('2G'))
            ->isEqualTo(2* 1024 *1024*1024);
    }

    /**
     * Test uploadStatus
     *
     * This must fail until files::uploadStatus don't handle UPLOAD_ERR_EXTENSION
     */
    public function testUploadStatus()
    {
        // Create a false $_FILES global without error
        $file = array(
            'name' => 'test.jpg',
            'size' => ini_get('post_max_size'),
            'tmp_name' => 'temptestname.jpg',
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/jpeg'
        );

        $this
            ->boolean(\files::uploadStatus($file))
            ->isTrue();

        // Simulate error
        $file['error'] = UPLOAD_ERR_INI_SIZE;
        $this ->exception(function() use ($file) { \files::uploadStatus($file); });

        $file['error'] = UPLOAD_ERR_FORM_SIZE;
        $this ->exception(function() use ($file) { \files::uploadStatus($file); });

        $file['error'] = UPLOAD_ERR_PARTIAL;
        $this ->exception(function() use ($file) { \files::uploadStatus($file); });

        $file['error'] = UPLOAD_ERR_NO_TMP_DIR; // Since PHP 5.0.3
        $this ->exception(function() use ($file) { \files::uploadStatus($file); });

        $file['error'] = UPLOAD_ERR_NO_FILE;
        $this ->exception(function() use ($file) { \files::uploadStatus($file); });

        $file['error'] = UPLOAD_ERR_CANT_WRITE;
        $this ->exception(function() use ($file) { \files::uploadStatus($file); });

        // This part might fail
        if (version_compare(phpversion(), '5.2.0', '>')) {
            $file['error'] = UPLOAD_ERR_EXTENSION;  // Since PHP 5.2
            $this ->exception(function() use ($file) { \files::uploadStatus($file); });
        }
    }

    public function testGetDirList()
    {
        \files::getDirList(TEST_DIRECTORY, $arr);
        $this
            ->array($arr)
            ->isNotEmpty()
            ->hasKeys(array('files', 'dirs'));

        $this
            ->array($arr['files'])
            ->isNotEmpty();

        $this
            ->array($arr['dirs'])
            ->isNotEmpty();
    }

    public function testTidyFilename()
    {
        $this
            ->string(\files::tidyFileName('a test file.txt'))
            ->isEqualTo('a_test_file.txt');
    }
}

class path extends atoum
{
    public function testRealUnstrict()
    {
        if (DIRECTORY_SEPARATOR == '\\')
        {
            // Hack to make it works under Windows
            $this
                ->string(str_replace('/', '\\', \path::real(__DIR__ . '/../fixtures/files', false)))
                ->isEqualTo(TEST_DIRECTORY);
        }
        else
        {
            $this
                ->string(\path::real(__DIR__ . '/../fixtures/files', false))
                ->isEqualTo(TEST_DIRECTORY);
        }
    }

    public function testRealStrict()
    {
        if (DIRECTORY_SEPARATOR == '\\')
        {
            // Hack to make it works under Windows
            $this
                ->string(str_replace('/', '\\', \path::real(__DIR__ . '/../fixtures/files', true)))
                ->isEqualTo(TEST_DIRECTORY);
        }
        else
        {
            $this
                ->string(\path::real(__DIR__ . '/../fixtures/files', true))
                ->isEqualTo(TEST_DIRECTORY);
        }
    }

    public function testClean()
    {
        $this
            ->string(\path::clean( '..' . DIRECTORY_SEPARATOR . 'testDirectory'))
            ->isEqualTo(DIRECTORY_SEPARATOR . 'testDirectory');
    }

    public function testInfo()
    {
        $info = \path::info(TEST_DIRECTORY . DIRECTORY_SEPARATOR . '1-one.txt');
        $this
            ->array($info)
            ->isNotEmpty()
            ->hasKeys(array('dirname', 'basename', 'extension', 'base'));

        $this
            ->string($info['dirname'])
            ->isEqualTo(TEST_DIRECTORY);

        $this
            ->string($info['basename'])
            ->isEqualTo('1-one.txt');

        $this
            ->string($info['extension'])
            ->isEqualTo('txt');

        $this
            ->string($info['base'])
            ->string('1-one');
    }

    public function testFullFromRoot()
    {
        $this
            ->string(\path::fullFromRoot('/test', '/'))
            ->isEqualTo('/test');

        $this
            ->string(\path::fullFromRoot('test/string', '/home/sweethome'))
            ->isEqualTo('/home/sweethome/test/string');
    }
}
