<script>
document.addEventListener('DOMContentLoaded',()=>{
 document.querySelectorAll('input[required],select[required],textarea[required]').forEach(c=>{
  let label=c.labels?.[0];
  if(!label){let p=c.parentElement;for(let n=0;n<3&&p&&!label;n++,p=p.parentElement)label=p.querySelector(':scope > label');}
  if(label&&!label.textContent.includes('*')){const s=document.createElement('span');s.textContent=' *';s.className='text-danger';s.title='Campo obligatorio';label.append(s);}
 });
});</script>