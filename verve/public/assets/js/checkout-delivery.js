(() => {
  const input = document.getElementById('delivery-location');
  const data = document.getElementById('delivery-pricing');
  if (!input || !data) return;
  const { zones, subtotal, discount, currency } = JSON.parse(data.textContent);
  const shipping = document.getElementById('delivery-fee');
  const total = document.getElementById('checkout-total');
  const button = document.getElementById('place-order');
  const note = document.getElementById('delivery-help');
  const money = value => currency + Number(value).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const normalize = value => {
    const name = value.trim().toLowerCase().replace(/[^\p{L}\p{N}]+/gu, ' ').replace(/\s+/g, ' ').trim();
    if (['cbd', 'central business district', 'city centre', 'nairobi city centre', 'nairobi central business district', 'nairobi cbd'].includes(name)) return 'nairobi cbd';
    if (['outside nairobi', 'out of nairobi'].includes(name)) return 'outside nairobi';
    return name;
  };
  const update = () => {
    const name = normalize(input.value);
    const zone = zones.find(item => normalize(item.name) === name) || (name.length >= 2 && name.length <= 100 && /\p{L}/u.test(name) ? zones.find(item => normalize(item.name) === 'outside nairobi') : null);
    input.setCustomValidity(zone ? '' : 'Choose a delivery location from the suggestions.');
    button.disabled = !zone;
    if (!zone) {
      shipping.textContent = 'Select location'; total.textContent = money(Math.max(0, subtotal - discount)) + ' + delivery';
      button.textContent = 'Select delivery location';
      note.textContent = name ? 'Choose a matching suggestion. Contact us if your area is not listed.' : 'Start typing and choose an area. Nairobi CBD delivery is free.';
      document.getElementById('country').value = '';
      return;
    }
    const fee = zone.free_over !== null && subtotal >= Number(zone.free_over) ? 0 : Number(zone.fee);
    const amount = Math.round((Math.max(0, subtotal - discount) + fee) * 100) / 100;
    shipping.textContent = fee === 0 ? 'Free' : money(fee);
    total.textContent = money(amount); button.textContent = 'Place order — ' + money(amount);
    document.getElementById('country').value = zone.country;
    note.textContent = zone.name + ' rate: ' + (fee === 0 ? 'Free' : money(fee)) + '. For Nairobi neighbourhoods select Nairobi; for the city centre type CBD.';
  };
  input.addEventListener('input', update); input.addEventListener('change', update); update();
})();
