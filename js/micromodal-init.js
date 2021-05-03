Drupal.behaviors.clnMicroModal = {
  attach(context) {

    const mm = context.classList && context.classList.contains('modal')
      ? [context] : context.querySelectorAll('.modal');
    if (mm.length === 0) { return; }

    MicroModal.init();

    // var button = document.querySelector('.myButton');
    // button.addEventListener('click', function(){
    //   MicroModal.show('video-remote-101');
    // });

    document.querySelectorAll('[data-micromodal-trigger]').forEach((link) => {
      link.addEventListener('click', (ev) => {
        ev.preventDefault();
      });
    });
  },
};
