
(function(){
  // Simple admin JS for bulk-checkboxes and AJAX actions
  document.addEventListener('DOMContentLoaded', function(){
    const selAll = document.querySelector('[data-gffm-select-all]');
    if(selAll){
      selAll.addEventListener('change', (e)=>{
        document.querySelectorAll('input[data-gffm-row]').forEach(cb=>{cb.checked = selAll.checked});
      });
    }
  });
})();
