

 

  var mycls = document.getElementsByClassName('nav-link');
  for (let i = 0; i < mycls.length; i++) {
    mycls[i].addEventListener("click", function () {
      for (var j = 0; j < mycls.length; j++) {
        mycls[j].classList.remove('active');
      }
      this.classList.add('active');
    });
  }

  function gotoNext(){
    document.getElementById('stepOne').classList.add('d-none');
    document.getElementById('stepTwo').classList.remove('d-none');
    document.getElementById('stepTwo').classList.add('d-block');
    document.getElementById('nxtBtn').classList.add('d-none');
    document.getElementById('prev').classList.remove('d-none');
    document.getElementById('prev').classList.remove('d-block');
  }

  function gotoPrev(){ 
    document.getElementById('stepTwo').classList.remove('d-block');
    document.getElementById('stepTwo').classList.add('d-none');
    document.getElementById('stepOne').classList.remove('d-none');
    document.getElementById('stepOne').classList.add('d-block');
    document.getElementById('nxtBtn').classList.remove('d-none');
    document.getElementById('nxtBtn').classList.add('d-block');
    document.getElementById('prev').classList.remove('d-block');
    document.getElementById('prev').classList.add('d-none');
  }