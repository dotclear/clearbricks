<?php
/**
 * @class template
 *
 * @package Clearbricks
 * @subpackage Template
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

class template
{
    private $self_name;

    public $use_cache = true;

    protected $blocks = [];
    protected $values = [];

    protected $remove_php = true;

    protected $unknown_value_handler = null;
    protected $unknown_block_handler = null;

    protected $tpl_path = [];
    protected $cache_dir;
    protected $parent_file;

    protected $compile_stack = [];
    protected $parent_stack  = [];

    # Inclusion variables
    protected static $superglobals = ['GLOBALS', '_SERVER', '_GET', '_POST', '_COOKIE', '_FILES', '_ENV', '_REQUEST', '_SESSION'];
    protected static $_k;
    protected static $_n;
    protected static $_r;

    public function __construct($cache_dir, $self_name)
    {
        $this->setCacheDir($cache_dir);

        $this->self_name = $self_name;
        $this->addValue('include', [$this, 'includeFile']);
        $this->addBlock('Block', [$this, 'blockSection']);
    }

    public function includeFile($attr)
    {
        if (!isset($attr['src'])) {return;}

        $src = path::clean($attr['src']);

        $tpl_file = $this->getFilePath($src);
        if (!$tpl_file) {return;}
        if (in_array($tpl_file, $this->compile_stack)) {return;}

        return
        '<?php try { ' .
        'echo ' . $this->self_name . "->getData('" . str_replace("'", "\'", $src) . "'); " .
            '} catch (Exception $e) {} ?>' . "\n";
    }

    public function blockSection($attr, $content)
    {
        return $content;
    }

    public function setPath()
    {
        $path = [];

        foreach (func_get_args() as $v) {
            if (is_array($v)) {
                $path = array_merge($path, array_values($v));
            } else {
                $path[] = $v;
            }
        }

        foreach ($path as $k => $v) {
            if (($v = path::real($v)) === false) {
                unset($path[$k]);
            }
        }

        $this->tpl_path = array_unique($path);
    }

    public function getPath()
    {
        return $this->tpl_path;
    }

    public function setCacheDir($dir)
    {
        if (!is_dir($dir)) {
            throw new Exception($dir . ' is not a valid directory.');
        }

        if (!is_writable($dir)) {
            throw new Exception($dir . ' is not writable.');
        }

        $this->cache_dir = path::real($dir) . '/';
    }

    public function addBlock($name, $callback)
    {
        if (!is_callable($callback)) {
            throw new Exception('No valid callback for ' . $name);
        }

        $this->blocks[$name] = $callback;
    }

    public function addValue($name, $callback)
    {
        if (!is_callable($callback)) {
            throw new Exception('No valid callback for ' . $name);
        }

        $this->values[$name] = $callback;
    }

    public function blockExists($name)
    {
        return isset($this->blocks[$name]);
    }

    public function valueExists($name)
    {
        return isset($this->values[$name]);
    }

    public function tagExists($name)
    {
        return $this->blockExists($name) || $this->valueExists($name);
    }

    public function getValueCallback($name)
    {
        if ($this->valueExists($name)) {
            return $this->values[$name];
        }

        return false;
    }

    public function getBlockCallback($name)
    {
        if ($this->blockExists($name)) {
            return $this->blocks[$name];
        }

        return false;
    }

    public function getBlocksList()
    {
        return array_keys($this->blocks);
    }

    public function getValuesList()
    {
        return array_keys($this->values);
    }

    public function getFile($file)
    {
        $tpl_file = $this->getFilePath($file);

        if (!$tpl_file) {
            throw new Exception('No template found for ' . $file);
            return false;
        }

        $file_md5  = md5($tpl_file);
        $dest_file = sprintf('%s/%s/%s/%s/%s.php',
            $this->cache_dir,
            'cbtpl',
            substr($file_md5, 0, 2),
            substr($file_md5, 2, 2),
            $file_md5
        );

        clearstatcache();
        $stat_f = $stat_d = false;
        if (file_exists($dest_file)) {
            $stat_f = stat($tpl_file);
            $stat_d = stat($dest_file);
        }

        # We create template if:
        # - dest_file doest not exists
        # - we don't want cache
        # - dest_file size == 0
        # - tpl_file is more recent thant dest_file
        if (!$stat_d || !$this->use_cache || $stat_d['size'] == 0 || $stat_f['mtime'] > $stat_d['mtime']) {
            files::makeDir(dirname($dest_file), true);

            if (($fp = @fopen($dest_file, 'wb')) === false) {
                throw new Exception('Unable to create cache file');
            }

            $fc = $this->compileFile($tpl_file);
            fwrite($fp, $fc);
            fclose($fp);
            files::inheritChmod($dest_file);
        }
        return $dest_file;
    }

    public function getFilePath($file)
    {
        foreach ($this->tpl_path as $p) {
            if (file_exists($p . '/' . $file)) {
                return $p . '/' . $file;
            }
        }

        return false;
    }

    public function getParentFilePath($previous_path, $file)
    {
        $check_file = false;
        foreach ($this->tpl_path as $p) {
            if ($check_file && file_exists($p . '/' . $file)) {
                return $p . "/" . $file;
            }
            if ($p == $previous_path) {
                $check_file = true;
            }
        }

        return false;
    }

    public function getData($________)
    {
        self::$_k = array_keys($GLOBALS);

        foreach (self::$_k as self::$_n) {
            if (!in_array(self::$_n, self::$superglobals)) {
                global ${self::$_n};
            }
        }
        $dest_file = $this->getFile($________);
        ob_start();
        if (ini_get('display_errors') == true) {
            include $dest_file;
        } else {
            @include $dest_file;
        }
        self::$_r = ob_get_contents();
        ob_end_clean();

        return self::$_r;
    }

    protected function getCompiledTree($file, &$err)
    {
        $fc = file_get_contents($file);

        $this->compile_stack[] = $file;

        # Remove every PHP tags
        if ($this->remove_php) {
            $fc = preg_replace('/<\?(?=php|=|\s).*?\?>/ms', '', $fc);
        }

        # Transform what could be considered as PHP short tags
        $fc = preg_replace('/(<\?(?!php|=|\s))(.*?)(\?>)/ms',
            '<?php echo "$1"; ?>$2<?php echo "$3"; ?>', $fc);

        # Remove template comments <!-- #... -->
        $fc = preg_replace('/(^\s*)?<!-- #(.*?)-->/ms', '', $fc);

        # Lexer part : split file into small pieces
        # each array entry will be either a tag or plain text
        $blocks = preg_split(
            '#(<tpl:\w+[^>]*>)|(</tpl:\w+>)|({{tpl:\w+[^}]*}})#msu', $fc, -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        # Next : build semantic tree from tokens.
        $rootNode          = new tplNode();
        $node              = $rootNode;
        $errors            = [];
        $this->parent_file = '';
        foreach ($blocks as $id => $block) {
            $isblock = preg_match('#<tpl:(\w+)(?:(\s+.*?)>|>)|</tpl:(\w+)>|{{tpl:(\w+)(\s(.*?))?}}#ms', $block, $match);
            if ($isblock == 1) {
                if (substr($match[0], 1, 1) == '/') {
                    // Closing tag, check if it matches current opened node
                    $tag = $match[3];
                    if (($node instanceof tplNodeBlock) && $node->getTag() == $tag) {
                        $node->setClosing();
                        $node = $node->getParent();
                    } else {
                        // Closing tag does not match opening tag
                        // Search if it closes a parent tag
                        $search = $node;
                        while ($search->getTag() != 'ROOT' && $search->getTag() != $tag) {
                            $search = $search->getParent();
                        }
                        if ($search->getTag() == $tag) {
                            $errors[] = sprintf(
                                __('Did not find closing tag for block <tpl:%s>. Content has been ignored.'),
                                html::escapeHTML($node->getTag()));
                            $search->setClosing();
                            $node = $search->getParent();
                        } else {
                            $errors[] = sprintf(
                                __('Unexpected closing tag </tpl:%s> found.'),
                                $tag);
                        }
                    }
                } elseif (substr($match[0], 0, 1) == '{') {
                    // Value tag
                    $tag      = $match[4];
                    $str_attr = '';
                    $attr     = [];
                    if (isset($match[6])) {
                        $str_attr = $match[6];
                        $attr     = $this->getAttrs($match[6]);
                    }
                    if (strtolower($tag) == "extends") {
                        if (isset($attr['parent']) && $this->parent_file == "") {
                            $this->parent_file = $attr['parent'];
                        }
                    } elseif (strtolower($tag) == "parent") {
                        $node->addChild(new tplNodeValueParent($tag, $attr, $str_attr));
                    } else {
                        $node->addChild(new tplNodeValue($tag, $attr, $str_attr));
                    }
                } else {
                    // Opening tag, create new node and dive into it
                    $tag = $match[1];
                    if ($tag == "Block") {
                        $newnode = new tplNodeBlockDefinition($tag, isset($match[2]) ? $this->getAttrs($match[2]) : []);
                    } else {
                        $newnode = new tplNodeBlock($tag, isset($match[2]) ? $this->getAttrs($match[2]) : []);
                    }
                    $node->addChild($newnode);
                    $node = $newnode;
                }
            } else {
                // Simple text
                $node->addChild(new tplNodeText($block));
            }
        }

        if (($node instanceof tplNodeBlock) && !$node->isClosed()) {
            $errors[] = sprintf(
                __('Did not find closing tag for block <tpl:%s>. Content has been ignored.'),
                html::escapeHTML($node->getTag()));
        }

        $err = "";
        if (count($errors) > 0) {
            $err = "\n\n<!-- \n" .
            __('WARNING: the following errors have been found while parsing template file :') .
            "\n * " .
            join("\n * ", $errors) .
                "\n -->\n";
        }
        return $rootNode;
    }

    protected function compileFile($file)
    {
        $tree = null;
        while (true) {
            if ($file && !in_array($file, $this->parent_stack)) {
                $tree = $this->getCompiledTree($file, $err);

                if ($this->parent_file == "__parent__") {
                    $this->parent_stack[] = $file;
                    $newfile              = $this->getParentFilePath(dirname($file), basename($file));
                    if (!$newfile) {
                        throw new Exception('No template found for ' . basename($file));
                        return false;
                    }
                    $file = $newfile;
                } elseif ($this->parent_file != "") {
                    $this->parent_stack[] = $file;
                    $file                 = $this->getFilePath($this->parent_file);
                    if (!$file) {
                        throw new Exception('No template found for ' . $this->parent_file);
                        return false;
                    }
                } else {
                    return $tree->compile($this) . $err;
                }
            } else {
                if ($tree != null) {
                    return $tree->compile($this) . $err;
                } else {
                    return '';
                }
            }
        }
    }

    public function compileBlockNode($tag, $attr, $content)
    {
        $res = '';
        if (isset($this->blocks[$tag])) {
            $res .= call_user_func($this->blocks[$tag], $attr, $content);
        } elseif ($this->unknown_block_handler != null) {
            $res .= call_user_func($this->unknown_block_handler, $tag, $attr, $content);
        }
        return $res;
    }

    public function compileValueNode($tag, $attr, $str_attr)
    {
        $res = '';
        if (isset($this->values[$tag])) {
            $res .= call_user_func($this->values[$tag], $attr, ltrim($str_attr));
        } elseif ($this->unknown_value_handler != null) {
            $res .= call_user_func($this->unknown_value_handler, $tag, $attr, $str_attr);
        }
        return $res;
    }

    protected function compileValue($match)
    {
        $v        = $match[1];
        $attr     = isset($match[2]) ? $this->getAttrs($match[2]) : [];
        $str_attr = isset($match[2]) ? $match[2] : null;

        return call_user_func($this->values[$v], $attr, ltrim($str_attr));
    }

    public function setUnknownValueHandler($callback)
    {
        if (is_callable($callback)) {
            $this->unknown_value_handler = $callback;
        }
    }

    public function setUnknownBlockHandler($callback)
    {
        if (is_callable($callback)) {
            $this->unknown_block_handler = $callback;
        }
    }

    protected function getAttrs($str)
    {
        $res = [];
        if (preg_match_all('|([a-zA-Z0-9_:-]+)="([^"]*)"|ms', $str, $m) > 0) {
            foreach ($m[1] as $i => $v) {
                $res[$v] = $m[2][$i];
            }
        }
        return $res;
    }
}
