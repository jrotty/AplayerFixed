<?php
/**
 * Aplayer网站背景音乐插件，支持InstantClick与常规pjax的主题
 * 
 * @package AplayerFixed
 * @author 泽泽
 * @version 1.1.1
 * @link https://store.typecho.work
 */
class AplayerFixed_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Archive')->header = array('AplayerFixed_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('AplayerFixed_Plugin', 'footer');
        Helper::addRoute("meting","/meting","AplayerFixed_Action",'action');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
        Helper::removeRoute("meting");
        self::deleteFile();
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
	    $type = new Typecho_Widget_Helper_Form_Element_Radio('type', array(
	        "InstantClick" => "InstantClick", 
	        "Pjax" => "常规Pjax"), "InstantClick",
			_t('适配类型'), "背景音乐切换页面不间断播放需要主题支持InstantClick或常规Pjax技术，然后根据类型在这里选择一下即可");
        $form->addInput($type);
        
        $getype = new Typecho_Widget_Helper_Form_Element_Radio(
            'getype', array('netease' => '网易云音乐(默认)', 'tencent' => 'QQ音乐'), 'netease', '歌曲源',
            '选择好后请在下方填写对应平台的歌单id即可');
        $form->addInput($getype);
        
        $gedanid = new Typecho_Widget_Helper_Form_Element_Text('gedanid', NULL, "876761898",
			_t('歌单id'), _t('请填写网易云的歌单id'));
        $form->addInput($gedanid);
        
	    $order = new Typecho_Widget_Helper_Form_Element_Radio('order', array(
	        "random" => "随机播放", 
	        "list" => "顺序播放"), "random",
			_t('播放顺序'), "");
        $form->addInput($order);
        
	    $weizhi = new Typecho_Widget_Helper_Form_Element_Radio('weizhi', array(
	        "left" => "左下角", 
	        "right" => "右下角"), "right",
			_t('显示位置'), "");
        $form->addInput($weizhi);
        
        $t = new Typecho_Widget_Helper_Form_Element_Text(
            'auth',
            null,
            Typecho_Common::randString(32),
            _t('* 接口保护'),
            _t('加盐保护 API 接口不被滥用，自动生成禁止自行设置。')
        );
        $t->setAttribute('class', 'hidden');
        $form->addInput($t);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 输出头部css
     * 
     * @access public
     * @param unknown $header
     * @return unknown
     */
    public static function header() {
        $options = Typecho_Widget::widget('Widget_Options');
        $set = $options->plugin('AplayerFixed');
        $cssUrl = Helper::options()->pluginUrl . '/AplayerFixed/';
         echo '<link rel="stylesheet" type="text/css" href="' . $cssUrl . 'APlayer.min.css" />';
         if($set->weizhi=='right'){
         echo '<link rel="stylesheet" type="text/css" href="' . $cssUrl . 'right.css" />';
         }
    }
    public static function footer() {
        $options = Typecho_Widget::widget('Widget_Options');
        $set = $options->plugin('AplayerFixed');
        $cssUrl = Helper::options()->pluginUrl . '/AplayerFixed/';
        $rewrite='';if(Helper::options()->rewrite==0){$rewrite='index.php/';}
        $apiurl=Helper::options()->rootUrl.'/'.$rewrite.'meting';
         echo '<script src="' . $cssUrl . 'APlayer.min.js" data-no-instant></script>';
         ?>
 <script data-no-instant>
(function () { function loadAPlayer(container, meting, arg) { 
const meting_api = '<?php echo $apiurl;?>?server=:server&type=:type&id=:id&r=:r'; 
let url = meting_api .replace(':server', meting.server) .replace(':type', meting.type) .replace(':id', meting.id) .replace(':auth', meting.auth) .replace(':r', Math.random());
    return new Promise((resolve) => {
        fetch(url)
            .then(res => res.json())
            .then(result => {
                const ap = _loadPlayer(result);
                resolve(ap);
            });
    });

    function _loadPlayer(music) {
        let defaultOption = {
            container: container,
            audio: music,
            lrcType: 3,
            loop: 'all',
            storageName: 'metingjs'
        };
        if (!music.length) {
            return;
        }
        if (!music[0].lrc) {
            defaultOption['lrcType'] = 0;
        }
        let options = {
            ...defaultOption,
            ...arg,
        };
        return new APlayer(options);
    }
}

async function initAPlayer(meting, arg) {
    // 创建播放器节点并添加到body中
    const div = document.createElement("div");
    document.body.appendChild(div);
    // 创建播放器
    const ap = await loadAPlayer(div, meting, arg);
    // 监听更改
    <?php if($set->type=='InstantClick'):?>
    InstantClick.on('change', function(isInitialLoad) {
        if (isInitialLoad === false) {document.body.appendChild(div);
    }});
    <?php endif; ?>
}

document.addEventListener('DOMContentLoaded', function () {
    if (window.APlayer && window.fetch) {
        const meting = {
            server: "<?php echo $set->getype; ?>",
            type: "playlist",
            id: "<?php echo $set->gedanid; ?>",
        };
        const arg = {
            fixed: true,
            mutex: true,
            volume: 1,
            loop: 'none',
            order: '<?php echo $set->order; ?>',
            preload: 'none',
            autoplay: false,
        };
        initAPlayer(meting, arg);
    }
});
})();</script>
         <?php
    }
    public static function deleteFile()
	{
		$path = __DIR__ . '/cache/playlist/';
		foreach (glob( $path. '*.json') as $filename) {
		   unlink($filename);
		}
	}
}
