<div class="container">
    <div class="row">
        <div class="col-sm-6 col-md-4 col-md-offset-4">
            <div class="account-wall">
                <img src="{dir}/img/logo.png" style="margin: auto;text-align: center;display: block;"/>
                <form action="?index=login" class="form-login" method="post" name="box-login" id="box-login" autocomplete="off">
                <input type="text" class="form-control" placeholder="{lang_username}" name="username" required autofocus>
                <input type="password" class="form-control" placeholder="{lang_password}" name="password" required>{alert}
                <button class="btn btn-lg btn-primary btn-block" name="login" value="true" type="submit">Login</button>
                <label class="checkbox pull-left"><input type="checkbox" name="autologin" value="remember-me">{lang_autologin}</label>
                <a href="#" class="pull-right need-help">{lang_need_help} </a><span class="clearfix"></span>
                </form>
            </div>
        </div>
    </div>
</div>