<form aria-hidden="true" class="search" role="search" method="get" action="<?php echo home_url( '/' ); ?>">
    <div class="grid">
        <input class="search__input" placeholder="Search..." type="text" name="s" id="s" />
        <label for="submit" class="search-perform">
            <i class="fas fa-search"></i>
        </label>
        <div class="search-cancel">
            <i class="fas fa-times"></i>
        </div>
        <input class="search__button" id="submit" type="submit" value="Search" />
    </div>
</form>  