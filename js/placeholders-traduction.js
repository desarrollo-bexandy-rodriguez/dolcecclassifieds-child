jQuery(document).ready(function($) {
    // Find all placeholders

  document.querySelector('html').addEventListener("DOMSubtreeModified", translationCallback, false);

  function translationCallback() {
                // This function needs to be called when Google translates this web page.

      var lang = document.querySelector('html').getAttribute('lang');
      var searchph = 'Search for';
      var locationph = 'Where?'

      switch (lang) {
        case 'en':
          searchph = 'Search for';
          locationph = 'Where?'
          break;
        case 'auto':
          searchph = 'Search for';
          locationph = 'Where?'
          break;
        case 'es':
          searchph = 'Buscar';
          locationph = '¿Dónde?'
          break;
        case 'fr':
          searchph = 'Rechercher';
          locationph = 'Où?'
          break;
        case 'pt':
          searchph = 'Procurar por';
          locationph = 'Onde?'
          break;
        default:
          searchph = 'Search for';
          locationph = 'Where?'
          break;
      }

      document.getElementById('s').setAttribute('placeholder',searchph);
      document.getElementById('location').setAttribute('placeholder',locationph);

  }
});