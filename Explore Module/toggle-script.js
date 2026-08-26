function toggleCuisineItems(btn) {
  const category = btn.closest('.cuisine-category');
  const itemsList = category.querySelector('.items-list');
  if (itemsList) {
    itemsList.classList.toggle('show');
    if (itemsList.classList.contains('show')) {
      btn.textContent = 'Hide Details';
    } else {
      btn.textContent = 'View Details';
    }
  }
}
