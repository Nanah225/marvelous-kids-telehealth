<!DOCTYPE html>
<html>
<head>
<title>Marvelous Kids</title>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Segoe UI',sans-serif;
}

body{
height:100vh;
display:flex;
justify-content:center;
align-items:center;
background:#eef7ff;
}

.phone{
width:390px;
height:800px;
background:#fff;
border-radius:40px;
overflow:hidden;
box-shadow:0 0 40px rgba(0,0,0,.2);

display:flex;
flex-direction:column;
justify-content:center;
align-items:center;
}

.logo-circle{
width:120px;
height:120px;
border-radius:50%;
background:linear-gradient(
135deg,
#2E8BFF,
#00C896
);

display:flex;
justify-content:center;
align-items:center;

font-size:55px;
color:white;
}

.title{
margin-top:25px;
font-size:30px;
font-weight:bold;
color:#2E8BFF;
}

.tagline{
margin-top:10px;
color:#666;
text-align:center;
padding:0 30px;
}

.loader{
margin-top:40px;
width:55px;
height:55px;
border:5px solid #ddd;
border-top:5px solid #2E8BFF;
border-radius:50%;
animation:spin 1s linear infinite;
}

@keyframes spin{
100%{
transform:rotate(360deg);
}
}

</style>
</head>

<body>

<div class="phone">

<div class="logo-circle">
  <img src="marvelous.png" alt="App Logo" style="width:400px;height:150px;border-radius:100%;">
</div>


<div class="title">
Marvelous Kids
</div>

<div class="tagline">
Caring For Little Heroes Everywhere
</div>

<div class="loader"></div>

</div>

<script>

setTimeout(function(){
window.location='register.php';
},3000);

</script>

</body>
</html>