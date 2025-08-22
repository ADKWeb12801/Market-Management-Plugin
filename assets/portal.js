(function($){
  const Portal = {
    init: function(){
      this.bindTabs();
      this.bindImages();
      this.bindPasswordToggle();
      this.bindAjaxForms();
    },
    bindTabs: function(){
      const setActive = function(tab){
        $('.gffm-tab-nav li').removeClass('active').attr('aria-selected','false');
        $('.gffm-tab-content').removeClass('active').attr('hidden', true);
        $('[data-tab="'+tab+'"]').addClass('active').attr('aria-selected','true');
        $('#gffm-tab-'+tab).addClass('active').attr('hidden', false);
      };
      const first = $('.gffm-tab-nav li.active').data('tab');
      if(first){ setActive(first); }
      $('.gffm-tab-nav li').on('click keypress', function(e){
        if(e.type === 'click' || e.key === 'Enter'){
          setActive($(this).data('tab'));
        }
      });
    },
    bindImages: function(){
      $(document).on('click','.gffm-img-btn',function(e){
        e.preventDefault();
        const target = $(this).data('target');
        const field = $('[name="'+target+'"]');
        const frame = wp.media({title:gffmPortal.i18n.select,multiple:false});
        frame.on('select',function(){
          const attachment = frame.state().get('selection').first().toJSON();
          field.val(attachment.id);
          field.siblings('.gffm-img-preview').html('<img src="'+attachment.url+'" />');
        });
        frame.open();
      });
    },
    bindPasswordToggle: function(){
      $(document).on('click','.gffm-toggle-pass',function(){
        const field = $('#gffm_password');
        if(field.attr('type') === 'password'){
          field.attr('type','text');
          $(this).attr('aria-label', gffmPortal.i18n.hide).text('🙈');
        }else{
          field.attr('type','password');
          $(this).attr('aria-label', gffmPortal.i18n.show).text('👁');
        }
      });
    },
    bindAjaxForms: function(){
      const notify = function(msg,type){
        const div = $('<div/>',{'class':'gffm-notice '+type,'aria-live':'polite',text:msg});
        $('.gffm-notice').remove();
        $('.gffm-portal-tabs').prepend(div);
      };
      $('#gffm-profile-form').on('submit', function(e){
        e.preventDefault();
        const nonce = $(this).find('[name="gffm_profile_nonce"]').val();
        $.post(gffmPortal.ajaxurl, $(this).serialize()+'&action=gffm_profile_save&nonce='+nonce, function(resp){
          const type = resp.success ? 'gffm-notice-info' : 'gffm-notice-error';
          notify(resp.data.message, type);
        });
      });
      $('#gffm-highlight-form').on('submit', function(e){
        e.preventDefault();
        const nonce = $(this).find('[name="gffm_highlight_nonce"]').val();
        $.post(gffmPortal.ajaxurl, $(this).serialize()+'&action=gffm_highlight_save&nonce='+nonce, function(resp){
          const type = resp.success ? 'gffm-notice-info' : 'gffm-notice-error';
          notify(resp.data.message, type);
        });
      });
    }
  };
  $(function(){ Portal.init(); });
})(jQuery);
