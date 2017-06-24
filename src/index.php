<?php
/**
 * Windwork
 *
 * 一个用于快速开发高并发Web应用的轻量级PHP框架
 *
 * @copyright Copyright (c) 2008-2017 Windwork Team. (http://www.windwork.org)
 * @license   http://opensource.org/licenses/MIT
 */
$job = @$argv[1];

// wf-tool 版本
const WF_TOOL_VERSION = '0.1.0';
const WF_TOOL_RELEASE = '2017-06-24 18:00';

if ($job && in_array($job, ['--version', '-v'])) {
    die('wf-tool ' . WF_TOOL_VERSION . ' (' . WF_TOOL_RELEASE. ')' . "\n");
}

if (!$job || in_array($job, ['--help', '-h', '-?', '?']) || !in_array($job, ['add'])) {
    dieUsage();
}

$appDir = getcwd();

if (basename($appDir) != 'app') {
    // 进入项目app文件夹才能执行命令，以防目录错误
    dieErr('please get into the windwork project\'s "app" folder.');
}

// 加载配置文件
$cfgPath = $appDir . "/../config/app.php";
if (!is_file($cfgPath)) {    
    dieErr('please confirm you are in the windwork project\'s "app" folder, and "../config/app.php" file exist.');
}

/*
print "\ninput args\n------------------\n";
print_r($argv);
print "-----------------\n";
*/

// 添加模块、控制器、操作、模型、服务时，如果启用模块并且模块不存在则自动创建模块
// 添加action时，如果控制器不存在则自动创建
$jobList = [
    // 添加操作
    // 使用驼峰命名规则，以小写字母开头，只允许包含字母和数字
    // 自动在操作名后面加Action
    // usage:
    // 1）不启用模块时：
    // 添加操作：\app\controller\OrderController::detailAction()
    // 输入命令：  wf-tool add act:detail ctl:Order
    // 2）启用模块时：
    // 添加操作： \app\mall\controller\OrderController::detailAction()
    // 输入命令：  wf-tool add act:detail ctl:Order mod:mall
    // 添加操作： \app\mall\controller\admin\trade\OrderController::detailAction()
    // 输入命令： wf-tool add act:detail ctl:admin.trade.Order mod:mall
    'addAct' => 'addAction',  
    
    // 添加控制器
    // 类名使用驼峰命名规则，以大写字母开头，只允许包含字母和数字
    // 子文件夹全部是小写，以字母开头，允许是字母和数字，以“.”作为分隔符
    // 自动在控制器类名后面加Controller
    // 控制器类放在 controller 文件夹中
    // 
    // usage：
    // 1）不启用模块时：
    // 创建控制器： \app\controller\OrderController
    // 输入命令为：  wf-tool add ctl:Order
    // 2）启用模块时：
    // 创建控制器： \app\mall\controller\OrderController
    // 输入命令为：  wf-tool add ctl:Order mod:mall
    // 创建控制器： \app\mall\controller\admin\trade\OrderController
    // 输入命令为：wf-tool add ctl:admin.trade.Order mod:mall
    'addCtl' => 'addController',  
    
    // 添加服务类
    // 类名使用驼峰命名规则，以大写字母开头，只允许包含字母和数字
    // 子文件夹全部是小写，以字母开头，允许是字母和数字，以“.”作为分隔符
    // 自动在服务类名后面加Service
    // 服务类放在 service 文件夹中
    // 
    // usage:
    // 1）不启用模块时：
    // 添加服务：\app\service\OrderService
    // 输入命令：  wf-tool add srv:Order
    // 2）启用模块时：
    // 添加服务： \app\mall\service\OrderService
    // 输入命令：  wf-tool add srv:Order mod:mall
    // 添加服务： \app\mall\service\trade\OrderService
    // 输入命令： wf-tool add srv:trade.Order mod:mall
    'addSrv' => 'addService',
    
    // 添加模型
    // 类名使用驼峰命名规则，以大写字母开头，只允许包含字母和数字
    // 子文件夹全部是小写，以字母开头，允许是字母和数字，以“.”作为分隔符
    // 自动在模型类名后面加Model
    // 模型类放在 model 文件夹中
    //
    // usage:
    // 1）不启用模块时：
    // 添加模型：\app\model\OrderModel
    // 输入命令：  wf-tool add model:Order
    // 2）启用模块时：
    // 添加模型： \app\mall\model\OrderModel
    // 输入命令：  wf-tool add model:Order mod:mall
    // 添加模型： \app\mall\model\trade\OrderModel
    // 输入命令： wf-tool add model:trade.Order mod:mall
    'addModel' => 'addModel',
    
    // 添加钩子类    
    // 类名使用驼峰命名规则，以大写字母开头，只允许包含字母和数字
    // 子文件夹全部是小写，以字母开头，允许是字母和数字，以“.”作为分隔符
    // 自动在钩子类名后面加Hook
    // 钩子类放在 hook 文件夹中
    //
    // usage:
    // 1）不启用模块时：
    // 添加钩子：\app\hook\OrderHook
    // 输入命令：  wf-tool add hook:Order
    // 2）启用模块时：
    // 添加钩子： \app\mall\hook\OrderHook
    // 输入命令：  wf-tool add hook:Order mod:mall
    // 添加钩子： \app\mall\hook\trade\OrderHook
    // 输入命令： wf-tool add hook:trade.Order mod:mall
    'addHook' => 'addHook',
    
    // 添加模块
    // - 要在配置中启用模块 url.useModule = true
    // - 模块名全部小写，以字母开头，只包含字母和数字
    // - 添加模块后，将自动在模块文件夹下创建service、model、controller、hook文件夹，
    //
    // usage:
    // 添加模块：\app\trade
    // 输入命令：  wf-tool add mod:trade
    'addMod' => 'addModule',  
];

$args = parseInput($argv);
$todo = @$args['todo'];
if (!$todo|| !array_key_exists($todo, $jobList)) {
    dieUsage();   
}

// 
require __DIR__ . '/DevelopTool.php';

$tool = new DevelopTool(include $cfgPath, $args);
$handler = $jobList[$todo];
$tool->$handler();

/**
 * 解析输入
 * @param array $argv
 * @return array
 */
function parseInput($argv)
{
    $args['todo'] = $argv[1] . ucfirst(strtolower(preg_replace("/:.*/", '', stripInputVar($argv[2]))));
    for ($i = 2; $i < count($argv); $i++) {
        list($key, $val) = @explode(':', stripInputVar($argv[$i]));
        // 下标只允许是字母、数字、下划线
        $key = preg_replace("/[^0-9a-z_]/i", '', $key);
        // 所有字母、数字、下划线之外的字符全部转成命名空间分隔符
        $val = preg_replace("/[^0-9a-z_]/i", '\\', $val);
        $val = preg_replace("/\\+/i", '\\', $val);
        $args[$key] = $val;
    }
    
    return $args;
}

/**
 * 
 * @param string $str
 * @return string
 */
function stripInputVar($str)
{
    return preg_replace("/[^0-9a-z_\\.\\:\\/\\\\]/i", '', $str);
}

/**
 * 显示错误信息并结束
 * @param string $msg
 */
function dieErr($msg)
{
    print "\nError:\n{$msg}\n\n";
    exit;
}

/**
 * 显示使用方法并结束程序
 */
function dieUsage()
{
    print "usage:\n";
    print "----------------------------------------\n";
    print "add module       php wf-tool.phar add mod:xxx\n";
    print "add action       php wf-tool.phar add act:xxx mod:xxx ctl:xxx\n";
    print "add controller   php wf-tool.phar add ctl:xxx mod:xxx\n";
    print "add model        php wf-tool.phar add model:xxx mod:xxx\n";
    print "add service      php wf-tool.phar add service:xxx mod:xxx\n";
    print "add hook         php wf-tool.phar add hook:xxx mod:xxx\n";
    print "----------------------------------------\n";
    print "@see https://github.com/windwork/wf-tool";
    exit;
}