fetch('http://localhost:8000/api/v1/admin/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
  body: JSON.stringify({ email: 'admin@atelier.com', password: 'Admin@123456' })
})
.then(async r => {
  const data = await r.json();
  console.log('HTTP Status:', r.status);
  console.log('Payload:', JSON.stringify(data, null, 2));
})
.catch(console.error);
