tinymce.init({
    selector: '.tinymce-paragraph',
    language: 'cs',
    height: 150,
    menubar: false,
    statusbar: false,
    plugins: 'autolink lists link code fullscreen paste',
    toolbar: 'undo redo | bold italic | bullist numlist | link unlink | code | fullscreen',
    paste_auto_cleanup_on_paste: true,
    convert_urls : false,
    relative_urls: false
});

tinymce.init({
    selector: '.tinymce',
    language: 'cs',
    height: 250,
    menubar: false,
    statusbar: false,
    plugins: 'autolink lists link code fullscreen paste',
    toolbar: 'undo redo | formatselect | bold italic | bullist numlist | link unlink | code | fullscreen',
    paste_auto_cleanup_on_paste: true,
    convert_urls : false,
    relative_urls: false,
    block_formats: 'Paragraph=p;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6;Preformatted=pre'
});

