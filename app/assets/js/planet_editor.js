window.MAP_VIEWER = {
    layers: {
        terrain: undefined,
        deposits: undefined
    },
    toolbar: {
        modes: undefined,
        terrain: undefined,
        deposits: undefined
    },
    data: {
        terrain: {},
        deposits: [],
    },
    map: undefined,
    canvas: undefined,
    editor: {
        mode: '',
        activeUid: '',
        mouseDown: false,
        terrainQueue: {debounceCounter: 0, queue: {}}
    },
};

window.MAP_VIEWER.recalculateTileSize = function () {
    const planetSize = Number(this.canvas.getAttribute('data-planet-size'));
    const canvasRect = this.canvas.getBoundingClientRect();
    const canvasWidth = canvasRect.width;
    const canvasHeight = canvasRect.height;

    const maxFittingTileWidth = Math.floor(canvasWidth / planetSize / 5) * 5;
    const maxFittingTileHeight = Math.floor(canvasHeight / planetSize / 5) * 5;

    const finalTileSize = Math.min(maxFittingTileWidth, maxFittingTileHeight, 50);
    const finalCanvasWidth = planetSize * finalTileSize;
    const marginLeft = Math.floor((canvasWidth - finalCanvasWidth) / 2);
    this.tileDimensions = {size: finalTileSize, marginLeft: marginLeft};
}

window.MAP_VIEWER.reRenderTiles = function() {
    const tileDimensions = this.tileDimensions;
    for (const tile of this.layers.terrain.children) {
        const x = tile.getAttribute('data-x');
        const y = tile.getAttribute('data-y');
        const terrainUid = tile.getAttribute('data-terrain');

        tile.style.width = tileDimensions.size + 'px';
        tile.style.height = tileDimensions.size + 'px';
        tile.style.top = (y * tileDimensions.size) + 'px'
        tile.style.left = (x * tileDimensions.size) + tileDimensions.marginLeft + 'px';
        tile.style.backgroundImage = `url(${this.data.terrain[terrainUid]})`;
    }
    this.layers.terrain.classList.remove('hidden')
}

window.MAP_VIEWER.changeMode = function(mode) {
    this.editor.mode = mode;
    for(const modeButton of this.toolbar.modes.children) {
        if(modeButton.getAttribute('data-mode') === mode) {
            modeButton.classList.add('active');
        } else {
            modeButton.classList.remove('active');
        }
    }
    if(mode === 'terrain')
        this.toolbar.terrain.classList.remove('hidden');
    else
        this.toolbar.terrain.classList.add('hidden')
    if(mode === 'deposits')
        this.toolbar.deposits.classList.remove('hidden');
    else
        this.toolbar.deposits.classList.add('hidden');
}

window.MAP_VIEWER.onModeButtonClicked = function (mode) {
    this.changeMode(this.editor.mode === mode ? '' : mode);
}

window.MAP_VIEWER.onTerrainTileClicked = function (tileClicked) {
    this.editor.activeUid = tileClicked.getAttribute('data-uid');
    for(const tile of window.MAP_VIEWER.toolbar.terrain.children) {
        tile.classList.remove('active')
    }
    tileClicked.classList.add('active')
}

window.MAP_VIEWER.onTileSelected = function (tile) {
    if(this.editor.mode !== 'terrain')
        return;

    tile.setAttribute('data-terrain', this.editor.activeUid);
    this.editor.terrainQueue.debounceCounter++;
    this.editor.terrainQueue.queue[tile.getAttribute('data-id')] = this.editor.activeUid;
    window.MAP_VIEWER.reRenderTiles();

    const debounce = this.editor.terrainQueue.debounceCounter;
    const timeoutFunc = async () => {
        if(debounce !== this.editor.terrainQueue.debounceCounter)
            return

        const updateRequestData = Object.entries(this.editor.terrainQueue.queue)
            .reduce((acc, [key, value]) => [...acc, {id: Number(key), terrainTypeUid: value}], [])
        const requestJson = JSON.stringify({grid: updateRequestData});
        const planetId = this.canvas.getAttribute('data-planet-id');
        this.editor.terrainQueue = {debounceCounter: 0, queue: []}

        await fetch(`/api/data/planets/${planetId}/terrain`, {method: 'PUT', body: requestJson, headers: {
            'Content-Type': 'application/json'
        }});
    }
    setTimeout(timeoutFunc.bind(this), 750)
}

async function hydrate() {
    window.MAP_VIEWER.data.terrain = await fetchTerrainTileMap();

    const map = document.getElementById('map');
    window.MAP_VIEWER.map = map;
    window.MAP_VIEWER.canvas = map.querySelector('.map__canvas');
    window.MAP_VIEWER.layers.terrain = map.querySelector('.map__terrain-layer');

    const toolbar = document.getElementById('toolbar');
    window.MAP_VIEWER.toolbar.modes = toolbar.querySelector('.toolbar__modes');
    window.MAP_VIEWER.toolbar.terrain = toolbar.querySelector('.toolbar__terrain');
    window.MAP_VIEWER.toolbar.deposits = toolbar.querySelector('.toolbar__deposits');

    window.MAP_VIEWER.recalculateTileSize();
    window.MAP_VIEWER.reRenderTiles();

    for(const modeButton of window.MAP_VIEWER.toolbar.modes.children) {
        modeButton.addEventListener('click', () => window.MAP_VIEWER.onModeButtonClicked(modeButton.getAttribute('data-mode')));
    }
    for(const tile of window.MAP_VIEWER.toolbar.terrain.children) {
        tile.addEventListener('click', () => window.MAP_VIEWER.onTerrainTileClicked(tile));
    }

    window.addEventListener('mousedown', (e) => {
        window.MAP_VIEWER.editor.mouseDown = true
        if(e.target.classList.contains('map__terrain-layer__tile') && window.MAP_VIEWER.editor.mode === 'terrain') {
            window.MAP_VIEWER.onTileSelected(e.target);
        }
    });
    window.addEventListener('mouseup', () => window.MAP_VIEWER.editor.mouseDown = false);
    map.querySelectorAll('.map__tile').forEach(tile => {
        tile.addEventListener('mousemove', (e) => {
            if(window.MAP_VIEWER.editor.mouseDown)
                window.MAP_VIEWER.onTileSelected(tile);
        })
    })
}

async function fetchTerrainTileMap() {
    const response = await fetch(`/api/definitions/terrain`);
    const responseJson = await response.json();

    return responseJson.reduce((map, item) => (
        {...map, [item.uid]: item.img.src}
    ), {})
}

if(document.readyState === 'complete') {
    hydrate();
} else {
    document.addEventListener('readystatechange', () => {
        if(document.readyState === 'complete') {
            hydrate();
        }
    })
}

window.addEventListener('resize', () => {
    window.MAP_VIEWER.recalculateTileSize();
    window.MAP_VIEWER.reRenderTiles();
})