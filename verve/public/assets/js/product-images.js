(() => {
  const input = document.getElementById('images');
  const list = document.getElementById('image-previews');
  const order = document.getElementById('image-order');
  const message = document.getElementById('image-message');
  if (!input || !list || !order || typeof DataTransfer === 'undefined') return;
  let files = [];
  let dragged = null;
  const removed = [];
  const removedInput = document.createElement('input'); removedInput.type = 'hidden'; removedInput.name = 'removed_images'; removedInput.value = '[]'; input.after(removedInput);
  const sync = () => {
    const cards = [...list.children];
    order.value = JSON.stringify(cards.map(card => card.dataset.key));
    order.disabled = false;
    cards.forEach((card, index) => {
      card.querySelector('.image-position').textContent = index === 0 ? 'Main photo' : `Photo ${index + 1}`;
      card.classList.toggle('is-main', index === 0);
    });
  };
  const wire = card => {
    card.draggable = true;
    card.addEventListener('dragstart', event => { dragged = card; event.dataTransfer.setData('text/plain', card.dataset.key); });
    card.addEventListener('dragover', event => event.preventDefault());
    card.addEventListener('drop', event => {
      event.preventDefault();
      if (dragged && dragged !== card) { list.insertBefore(dragged, card); sync(); }
      dragged = null;
    });
    card.addEventListener('dragend', () => { dragged = null; });
    const actions = document.createElement('div');
    actions.className = 'image-card-actions';
    [['Make main', () => list.prepend(card)], ['Move left', () => { if (card.previousElementSibling) list.insertBefore(card, card.previousElementSibling); }], ['Move right', () => { if (card.nextElementSibling) list.insertBefore(card.nextElementSibling, card); }]].forEach(([label, move]) => {
      const button = document.createElement('button');
      button.type = 'button'; button.className = 'btn btn-outline btn-sm'; button.textContent = label;
      button.addEventListener('click', () => { move(); sync(); message.textContent = 'Image order updated. Save product to apply.'; });
      actions.append(button);
    });
    card.append(actions);
    const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-outline btn-sm'; remove.textContent = 'Remove';
    remove.addEventListener('click', () => {
      if (card.dataset.key.startsWith('existing:')) { removed.push(card.dataset.key.slice(9)); removedInput.value = JSON.stringify(removed); }
      else {
        const index = Number(card.dataset.key.slice(4)); files.splice(index, 1);
        [...list.children].forEach(other => { if (other.dataset.key.startsWith('new:') && Number(other.dataset.key.slice(4)) > index) other.dataset.key = `new:${Number(other.dataset.key.slice(4))-1}`; });
        const transfer = new DataTransfer(); files.forEach(file => transfer.items.add(file)); input.files = transfer.files;
      }
      card.remove(); sync(); message.textContent = 'Photo removed from the selection. Save product to apply.';
    }); actions.append(remove);
  };
  [...list.children].forEach(wire);
  sync();
  input.addEventListener('change', () => {
    const selected = [...input.files];
    const accepted = selected.filter(file => ['image/jpeg', 'image/png', 'image/webp'].includes(file.type) && file.size <= 5 * 1024 * 1024);
    const limit = Number(input.dataset.maxFiles);
    if (accepted.length !== selected.length || files.length + selected.length > limit) {
      message.textContent = `Choose JPG, PNG or WebP files under 5 MB. Maximum ${limit} new images per save.`;
    } else {
      accepted.forEach(file => {
        const card = document.createElement('div'); card.className = 'product-image-card'; card.dataset.key = `new:${files.length}`;
        const image = document.createElement('img'); image.src = URL.createObjectURL(file); image.alt = file.name;
        image.addEventListener('load', () => URL.revokeObjectURL(image.src), { once: true });
        const badge = document.createElement('span'); badge.className = 'image-position';
        card.append(image, badge); files.push(file); wire(card); list.append(card);
      });
      message.textContent = 'Drag photos to reorder, or use the buttons. The first photo is the main picture.';
    }
    const transfer = new DataTransfer(); files.forEach(file => transfer.items.add(file)); input.files = transfer.files;
    sync();
  });
})();
