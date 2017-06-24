<?php
/**
 * Windwork
 *
 * 一个用于快速开发高并发Web应用的轻量级PHP框架
 *
 * @copyright Copyright (c) 2008-2017 Windwork Team. (http://www.windwork.org)
 * @license   http://opensource.org/licenses/MIT
 */

define('ROOT_DIR', dirname(getcwd()) . '/'); // 应用文件夹
define('APP_DIR', getcwd() . '/'); // 应用文件夹

/**
 * 开发工具
 *
 * @package   wf-tool
 * @author    cm <cmpan@qq.com>
 * @link      http://github.com/windwork/wf-tool
 * @since     0.1.0
 */
class DevelopTool
{
    protected $cfg;
    protected $args;
    
    /**
     * 
     * @param array $cfg
     * @param array $args
     */
    public function __construct($cfg, $args)
    {
        $this->cfg = $cfg;
        $this->args = $args;
    }
    
    /**
     * 添加模块
     * @param bool $existDie = true 存在的时候是否结束程序
     */
    public function addModule($existDie = true)
    {
        // 是否启用模块
        if (!$this->isModuleEnabled()) {
            dieErr("module is disabled!\nIf you would like to enable module, 'useModule' option must be set as true in '../config/url.php'");
        }
                
        $mod = @$this->args['mod'];        
        if (!$mod) {
            dieErr('Please set mod:xxx param');
        }
        
        // 模块名只允许是字母和数字
        $mod= preg_replace("/[^0-9a-z]/i", '', strtolower($mod));
        
        // 模块目录     
        $modDir = APP_DIR . "/{$mod}";
        
        // 模块是否已存在        
        if (is_dir($modDir)) {
            // 模块已存在，如果不结束程序则返回
            if (!$existDie) {
                print "The module '{$mod}' is exist!\n";
                return;
            }
            dieErr("The module '{$mod}' is exist!\n");
        }
                
        // 创建模块        
        if (!is_writeable(APP_DIR)) {
            die("The dir '" . APP_DIR . "' is not writeable!\n");
        }
        
        mkdir($modDir, 0755);
        mkdir($modDir. '/model', 0755);
        mkdir($modDir. '/controller', 0755);
        mkdir($modDir. '/service', 0755);
        mkdir($modDir. '/hook', 0755);
        
        print "Add the module '{$mod}' success!\n";
    }
    
    /**
     * 添加控制器类
     * @param bool $existDie = true 存在的时候是否结束程序
     */
    public function addController($existDie = true)
    {
        // 是否启用模块
        $mod = $this->getModuleInput();
        
        // 模块不存在则创建
        if ($mod) {
            $this->addModule(false);
        }
        
        // 控制器
        $ctl = @$this->args['ctl'];
        if (!$ctl) {
            dieErr('Please set the ctl:xxx param!');
        }
        $ctl= preg_replace("/[^0-9a-z\\.\\\\]/i", '', strtolower($ctl));
        
        // 控制器类名
        $class = preg_replace("/\\\\+/", '\\', strtolower("app\\{$mod}\\controller\\{$ctl}"));
        $classNode = explode("\\", $class);
        $classNode[count($classNode) - 1] = ucfirst($classNode[count($classNode) - 1]) . 'Controller';
        $className = implode('\\', $classNode);
        $classLastName = $classNode[count($classNode) - 1];
        
        // 控制器命名空间
        $namespace = substr($class, 0, strrpos($class, '\\'));
        
        // 控制器类文件
        $ctlFile = ROOT_DIR . str_replace("\\", '/', $className) . '.php';
        
        // 控制器类是否已存在
        if (is_file($ctlFile)) {
            // 控制器类已存在，如果不结束程序则返回
            if (!$existDie) {
                print "The controller file '{$ctlFile}' is exist!\n";
                return $ctlFile;
            }
            dieErr("The controller file '{$ctlFile}' is exist!");
        }
        
        // 创建控制器所在文件夹
        $dir = dirname($ctlFile);
        if(!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        
        if (!is_writeable($dir)) {
            dieErr("The dir '{$dir}' is not writeable");
        }
        
        // 创建控制器类
        $txt = file_get_contents(__DIR__ . '/tpl/controller.php');
        
        // 参数替换
        $txt = str_replace(
            ['THE_YEAR', 'THE_NAMESPACE', 'THE_PACKAGE', 'THE_CLASS_LAST_NAME'], 
            [
                date('Y'), 
                $namespace,
                strtr($namespace, '\\', '.'), 
                $classLastName,
            ],
            $txt
        );
        
        file_put_contents($ctlFile, $txt);
        
        print "Add the controller '{$className}' success!\n";
        
        return $ctlFile;
    }
    
    /**
     * 添加action
     */
    public function addAction()
    {
        $act = @$this->args['act'];
        if (empty($act)) {
            dieErr('Please set the act:xxx param!');
        }
        // 操作名只允许是字母和数字
        $act = preg_replace("/[^0-9a-z]/i", '', lcfirst($act)) . 'Action';
        
        $ctlFile = $this->addController(false);
        if (!is_writable($ctlFile)) {
            dieErr('The controller file "' . $ctlFile. '" is not writeable!');
        }
        
        // 检查操作方法是否已存在
        $ctlTxt = file_get_contents($ctlFile);
        
        // function xxxAction()
        if(preg_match("/function\\s+{$act}\\s?\\(.*?\\)/i", $ctlTxt)) {
            dieErr("The action '{$act}' is exist!");
        }
        
        // 添加方法
        $txt = file_get_contents(__DIR__ . '/tpl/action.php');
        
        // 参数替换
        $txt = str_replace('THE_ACTIONE_NAME', $act, $txt);
        $pos = strrpos($ctlTxt, '}');
        
        if (!$pos) {
            dieErr("The controller '{$ctlFile}' file's code error!");
        }
        
        $newCtlTxt = substr($ctlTxt, 0, $pos) . "\n" . $txt . "\n" . substr($ctlTxt, $pos);
        file_put_contents($ctlFile, $newCtlTxt);
        
        print "Add the action '{$act}' into '{$ctlFile}' success!\n";
    }
    
    /**
     * 添加服务
     */
    public function addService()
    {
        // 是否启用模块
        $mod = $this->getModuleInput();
        
        // 模块不存在则创建
        if ($mod) {
            $this->addModule(false);
        }
        
        // 服务
        $srv = @$this->args['srv'];
        if (!$srv) {
            dieErr('Please set the srv:xxx param!');
        }
        $srv = preg_replace("/[^0-9a-z\\.\\\\]/i", '', $srv); // 所有非法字符都清掉
        
        // 服务类名
        $class = preg_replace("/\\\\+/", '\\', strtolower("app\\{$mod}\\service\\{$srv}"));
        $classNode = explode("\\", $class);
        $classNode[count($classNode) - 1] = ucfirst($classNode[count($classNode) - 1]) . 'Service';
        $className = implode('\\', $classNode);
        $classLastName = $classNode[count($classNode) - 1];
        
        // 服务命名空间
        $namespace = substr($class, 0, strrpos($class, '\\'));
        
        // 服务文件
        $srvFile = ROOT_DIR . str_replace("\\", '/', $className) . '.php';
        
        // 服务类是否已存在
        if (is_file($srvFile)) {
            dieErr("The service file '{$srvFile}' is exist!");
        }
        
        // 创建服务类所在文件夹
        $dir = dirname($srvFile);
        if(!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        
        if (!is_writeable($dir)) {
            dieErr("The dir '{$dir}' is not writeable");
        }
        
        // 服务类模板
        $txt = file_get_contents(__DIR__ . '/tpl/service.php');
        
        // 参数替换
        $txt = str_replace(
            ['THE_YEAR', 'THE_NAMESPACE', 'THE_PACKAGE', 'THE_CLASS_LAST_NAME'],
            [
                date('Y'),
                $namespace,
                strtr($namespace, '\\', '.'),
                $classLastName,
            ],
            $txt
        );
        
        file_put_contents($srvFile, $txt);
        
        print "Add the service '{$className}' success!\n";
        
    }
    
    /**
     * 添加模型
     */
    public function addModel()
    {
        // 是否启用模块
        $mod = $this->getModuleInput();
        
        // 模块不存在则创建
        if ($mod) {
            $this->addModule(false);
        }
        
        // 模型
        $model = @$this->args['model'];
        if (!$model) {
            dieErr('Please set the model:xxx param!');
        }
        $model = preg_replace("/[^0-9a-z\\.\\\\]/i", '', $model); // 所有非法字符都清掉
        
        // 模型类名
        $class = preg_replace("/\\\\+/", '\\', strtolower("app\\{$mod}\\model\\{$model}"));
        $classNode = explode("\\", $class);
        $classNode[count($classNode) - 1] = ucfirst($classNode[count($classNode) - 1]) . 'Model';
        $className = implode('\\', $classNode);
        $classLastName = $classNode[count($classNode) - 1];
        
        // 命名空间
        $namespace = substr($class, 0, strrpos($class, '\\'));
        
        // 类文件
        $modelFile = ROOT_DIR . str_replace("\\", '/', $className) . '.php';
        
        // 模型类是否已存在
        if (is_file($modelFile)) {
            dieErr("The model file '{$modelFile}' is exist!");
        }
        
        // 创建模型类所在文件夹
        $dir = dirname($modelFile);
        if(!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        
        if (!is_writeable($dir)) {
            dieErr("The dir '{$dir}' is not writeable");
        }
        
        // 创建模型类
        $txt = file_get_contents(__DIR__ . '/tpl/model.php');
        
        // 参数替换
        $txt = str_replace(
            ['THE_YEAR', 'THE_NAMESPACE', 'THE_PACKAGE', 'THE_CLASS_LAST_NAME'],
            [
                date('Y'),
                $namespace,
                strtr($namespace, '\\', '.'),
                $classLastName,
            ],
            $txt
        );
        
        file_put_contents($modelFile, $txt);
        
        print "Add the model '{$className}' success!\n";
        
    }
    
    /**
     * 添加钩子
     */
    public function addHook()
    {
        // 是否启用模块
        $mod = $this->getModuleInput();
        
        // 模块不存在则创建
        if ($mod) {
            $this->addModule(false);
        }
        
        // 钩子
        $hook = @$this->args['hook'];
        if (!$hook) {
            dieErr('Please set the hook:xxx param!');
        }
        $hook= preg_replace("/[^0-9a-z\\.\\\\]/i", '', $hook); // 所有非法字符都清掉
        
        // 钩子类名
        $class = preg_replace("/\\\\+/", '\\', strtolower("app\\{$mod}\\hook\\{$hook}"));
        $classNode = explode("\\", $class);
        $classNode[count($classNode) - 1] = ucfirst($classNode[count($classNode) - 1]) . 'Hook';
        $className = implode('\\', $classNode);
        $classLastName = $classNode[count($classNode) - 1];
        
        // 钩子命名空间
        $namespace = substr($class, 0, strrpos($class, '\\'));
        
        // 钩子文件
        $hookFile = ROOT_DIR . str_replace("\\", '/', $className) . '.php';
        
        // 钩子类是否已存在
        if (is_file($hookFile)) {
            dieErr("The hook file '{$hookFile}' is exist!");
        }
        
        // 创建钩子类所在文件夹
        $dir = dirname($hookFile);
        if(!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        
        if (!is_writeable($dir)) {
            dieErr("The dir '{$dir}' is not writeable");
        }
        
        // 钩子类模板
        $txt = file_get_contents(__DIR__ . '/tpl/hook.php');
        
        // 参数替换
        $txt = str_replace(
            ['THE_YEAR', 'THE_NAMESPACE', 'THE_PACKAGE', 'THE_CLASS_LAST_NAME'],
            [
                date('Y'),
                $namespace,
                strtr($namespace, '\\', '.'),
                $classLastName,
            ],
            $txt
        );
        
        file_put_contents($hookFile, $txt);
        
        print "Add the hook '{$className}' success!\n";
        
    }
    
    /**
     * 获取模块参数
     */
    protected function getModuleInput()
    {        
        $mod = @$this->args['mod'];
        
        // 未启用模块输入模块参数，提示开启
        if (!$this->isModuleEnabled() && $mod) {
            dieErr("module is disabled!\nIf you would like to enable module, 'useModule' option must be set as true in '../config/url.php'");
        }
        
        // 已启用模块，未输入模块参数，提示输入
        if ($this->isModuleEnabled() && !$mod) {
            dieErr('Please set mod:xxx param');
        }
        
        return $mod;
    }
    
    /**
     * 应用示范已启用模块
     * @return boolean
     */
    protected function isModuleEnabled()
    {
        return !empty($this->cfg['url']['useModule']);
    }    
    
}
