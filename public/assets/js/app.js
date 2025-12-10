document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.flash').forEach(node => {
    node.addEventListener('click', () => node.classList.add('flash-hidden'));
    setTimeout(() => node.classList.add('flash-hidden'), 6000);
  });
});
