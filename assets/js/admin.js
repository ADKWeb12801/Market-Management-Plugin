
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    const selAll = document.querySelector('[data-gffm-select-all]');
    if(selAll){
      selAll.addEventListener('change', ()=>{
        document.querySelectorAll('input[data-gffm-row]').forEach(cb=>{cb.checked = selAll.checked;});
      });
    }
    const copyBtn = document.querySelector('.gffm-copy-redirect');
    if(copyBtn){
      const fb = document.querySelector('.gffm-copy-feedback');
      copyBtn.addEventListener('click', function(){
        navigator.clipboard.writeText(copyBtn.dataset.copy).then(function(){
          if(fb){ fb.style.display='inline'; setTimeout(()=>{fb.style.display='none';},2000); }
        });
      });
    }
    const ta = document.getElementById('gffm_profile_map_json');
    const badge = document.getElementById('gffm-json-valid');
    if(ta && badge){
      const validate = function(){
        try{ JSON.parse(ta.value); badge.textContent = gffmAdmin.i18n.jsonValid; badge.style.color='green'; }
        catch(e){ badge.textContent = gffmAdmin.i18n.jsonInvalid; badge.style.color='red'; }
      };
      ta.addEventListener('keyup', validate);
      validate();
    }
  });
})();
