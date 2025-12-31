<ul class="account-nav">
    <li><a href="{{route('home.index')}}" class="menu-link menu-link_us-s">Home</a></li>
    
            
    <li>
        <form method="POST" action="{{route('logout')}}" id="logout-form"> 
            @csrf
            <a href="{{route('logout')}}" class="menu-link menu-link_us-s" onclick="event.preventDefault();document.getElementById('logout-form').submit();">Logout</a>
        </form>   
    </li>
</ul>