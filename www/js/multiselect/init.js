/**
 * Created with JetBrains PhpStorm.
 * User: Michal
 * Date: 6.3.13
 * Time: 17:00
 * Author: Michal Májský
 */
$(function(){
    $.localise('ui-multiselect', { language: 'cs', path: basePath+'/js/multiselect/locale/'});
    $(".multiselect").multiselect();
});
