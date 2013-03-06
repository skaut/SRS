/**
 * Created with JetBrains PhpStorm.
 * User: Michal
 * Date: 6.3.13
 * Time: 17:00
 * To change this template use File | Settings | File Templates.
 */
$(function(){
    $.localise('ui-multiselect', { language: 'cs', path: basePath+'/js/multiselect/locale/'});
    $(".multiselect").multiselect();
});
