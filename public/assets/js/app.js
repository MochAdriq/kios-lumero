(function(){
  'use strict';
  document.addEventListener('click', function(e){
    const link = e.target.closest('.sim-menu-link');
    if(link && window.innerWidth < 992){ document.body.classList.remove('sidebar-open'); }
  });
})();
