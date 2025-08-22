(function($){
  $(document).on('click','.gffm-tab-nav li',function(){
    var tab = $(this).data('tab');
    $('.gffm-tab-nav li').removeClass('active');
    $(this).addClass('active');
    $('.gffm-tab-content').removeClass('active');
    $('#gffm-tab-'+tab).addClass('active');
  });
  $(document).on('click','.gffm-img-btn',function(e){
    e.preventDefault();
    var target = $(this).data('target');
    var field = $('[name="'+target+'"]');
    var frame = wp.media({title:'Select Image',multiple:false});
    frame.on('select',function(){
      var attachment = frame.state().get('selection').first().toJSON();
      field.val(attachment.id);
      field.siblings('.gffm-img-preview').html('<img src="'+attachment.url+'" />');
    });
    frame.open();
  });
})(jQuery);
