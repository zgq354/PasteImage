<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 在编辑器中一键粘贴图片，类似简书的编辑框
 *
 * @package Paste Image
 * @author qing
 * @version 1.0.0
 * @link http://izgq.net/
 */
class PasteImage_Plugin implements Typecho_Plugin_Interface
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
        // 在编辑文章和编辑页面的底部注入代码
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('PasteImage_Plugin', 'render');
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array('PasteImage_Plugin', 'render');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /** 分类名称 */
        $name = new Typecho_Widget_Helper_Form_Element_Text('word', NULL, 'Hello World', _t('说点什么'));
        $form->addInput($name);
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
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function render()
    {
        ?>
<script>
// 粘贴文件上传
$(document).ready(function () {
    // 上传URL
    var uploadUrl = '<?php Helper::security()->index('/action/upload'); ?>';
    // 处理有特定的 CID 的情况
    var cid = $('input[name="cid"]').val();
    if (cid) {
        uploadUrl += '&cid=' + cid;
    }

    // 上传文件函数
    function uploadFile(file) {
        console.log(file);

        // 生成一段随机的字符串作为 key
        var index = Math.random().toString(10).substr(2, 5) + '-' + Math.random().toString(36).substr(2);
        // 默认文件后缀是 png，在Chrome浏览器中剪贴板粘贴的图片都是png格式，其他浏览器暂未测试
        var fileName = index + '.png';

        // 上传时候提示的文字
        var uploadingText = '[图片上传中...(' + index + ')]';

        // 先把这段文字插入
        var textarea = $('#text'), sel = textarea.getSelection(),
        offset = (sel ? sel.start : 0) + uploadingText.length;
        textarea.replaceSelection(uploadingText);
        // 设置光标位置
        textarea.setSelection(offset, offset);

        // 是时候展示真正的上传了
        var formData = new FormData();
        formData.append('name', fileName);
        formData.append('file', file, fileName);

        $.ajax({
            method: 'post',
            url: uploadUrl,
            data: formData,
            contentType: false,
            processData: false,
            success: function (data) {
                console.log(data);
                var url = data[0], title = data[1].title;
                textarea.val(textarea.val().replace(uploadingText, '![' + title + '](' + url + ')'));
                // 触发输入框更新事件，把状态压人栈中，解决预览不更新的问题
                textarea.trigger('paste');
            },
            error: function (error) {
                textarea.val(textarea.val().replace(uploadingText, '[图片上传错误...]\n'));
                // 触发输入框更新事件，把状态压人栈中，解决预览不更新的问题
                textarea.trigger('paste');
            }
        });
    }

    // 监听输入框粘贴事件
    document.getElementById('text').addEventListener('paste', function (e) {
      var clipboardData = e.clipboardData;
      var items = clipboardData.items;
      for (var i = 0; i < items.length; i++) {
        console.log(items[i]);
        if (items[i].kind === 'file' && items[i].type.match(/^image/)) {
          // 取消默认的粘贴操作
          e.preventDefault();
          // 上传文件
          uploadFile(items[i].getAsFile());
          break;
        }
      }
    })
})
</script>
<?php
    }
}
