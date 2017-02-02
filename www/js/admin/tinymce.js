tinymce.init({
    selector: '.tinymce-paragraph',
    language: 'cs',
    height: 150,
    menubar: false,
    statusbar: false,
    plugins: 'autolink lists link code fullscreen paste',
    toolbar: 'undo redo | bold italic | bullist numlist | link unlink | code | fullscreen',
    paste_auto_cleanup_on_paste: true,
    relative_urls: false
});

tinymce.init({
    selector: '.tinymce',
    language: 'cs',
    height: 300,
    menubar: false,
    statusbar: false,
    plugins: 'autolink lists link code fullscreen paste',
    toolbar: 'undo redo | formatselect | bold italic | bullist numlist | link unlink | code | fullscreen', //TODO omezeni formatu
    paste_auto_cleanup_on_paste: true,
    relative_urls: false
});

