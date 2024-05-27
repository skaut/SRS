'use strict';

require('../common/main');

// tinymce
import tinymce from 'tinymce';
import 'tinymce/themes/silver';
import 'tinymce/skins/ui/oxide/skin.css'
import 'tinymce-i18n/langs/cs';
import 'tinymce/models/dom'
import 'tinymce/icons/default/icons';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/code';
import 'tinymce/plugins/fullscreen';
import contentUiCss from '!!raw-loader!tinymce/skins/ui/oxide/content.css';
import contentCss from '!!raw-loader!tinymce/skins/content/default/content.css';

tinymce.init({
    selector: '.tinymce-paragraph',
    language: 'cs',
    height: 150,
    menubar: false,
    statusbar: false,
    skin: false,
    content_css: false,
    content_style: contentUiCss.toString() + '\n' + contentCss.toString(),
    plugins: 'autolink lists link code fullscreen',
    toolbar: 'undo redo | bold italic | bullist numlist | link unlink | code | fullscreen',
    paste_auto_cleanup_on_paste: true,
    convert_urls : false,
    relative_urls: false,
    license_key: 'gpl',
});

tinymce.init({
    selector: '.tinymce',
    language: 'cs',
    height: 250,
    menubar: false,
    statusbar: false,
    skin: false,
    content_css: false,
    content_style: contentUiCss.toString() + '\n' + contentCss.toString(),
    plugins: 'autolink lists link code fullscreen',
    toolbar: 'undo redo | blocks | bold italic | bullist numlist | link unlink | code | fullscreen',
    paste_auto_cleanup_on_paste: true,
    convert_urls : false,
    relative_urls: false,
    block_formats: 'Paragraph=p;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6;Preformatted=pre',
    license_key: 'gpl',
});

// generování slugu
import slugify from 'slugify';
naja.addEventListener('complete', () => {
    $('#frm-pagesGrid-pagesGrid-filter-inline_add-name').keyup(function() {
        $('#frm-pagesGrid-pagesGrid-filter-inline_add-slug').val(slugify($(this).val(), {lower: true}));
    });
});

// kalendář programů
import './schedule/main'
