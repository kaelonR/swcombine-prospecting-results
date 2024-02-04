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
    tooltip: undefined,
    editor: {
        mode: '',
        activeUid: '',
        mouseDown: false,
        terrainQueue: {debounceCounter: 0, queue: {}},
        deposits: {}
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
        tile.style.backgroundImage = `url(${this.data.terrain[terrainUid].src})`;
    }
    this.layers.terrain.classList.remove('hidden')

    for(const tile of this.layers.deposits.children) {
        const x = tile.getAttribute('data-x')
        const y = tile.getAttribute('data-y')
        const depositTypeUid = tile.getAttribute('data-deposit');

        tile.style.width = tileDimensions.size + 'px';
        tile.style.height = tileDimensions.size + 'px';
        tile.style.top = (y * tileDimensions.size) + 'px';
        tile.style.left = (x * tileDimensions.size) + tileDimensions.marginLeft + 'px';
        tile.style.backgroundImage = depositTypeUid.length > 0 ? `url(${this.data.deposits[depositTypeUid].src})` : '';
    }
    this.layers.deposits.classList.remove('hidden')
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

window.MAP_VIEWER.renderTooltip = function(tile, e) {
    const tooltip = this.tooltip;
    tooltip.style.visibility = 'visible';

    const tooltipTitle = `${tile.getAttribute('data-x')}, ${tile.getAttribute('data-y')}`;
    tooltip.querySelector('#tooltip-top').innerText = tooltipTitle

    const x = tile.getAttribute('data-x');
    const y = tile.getAttribute('data-y');
    let tooltipBottomHtml = this.data.terrain[tile.getAttribute('data-terrain')].name;
    if(this.editor.deposits.hasOwnProperty(`${x}:${y}`)) {
        const depositData = this.editor.deposits[`${x}:${y}`];
        tooltipBottomHtml += `<br/>${this.data.deposits[depositData.depositTypeUid].name} (${depositData.amount} units)`
        if(depositData.notes.length) {
            tooltipBottomHtml += `<br/>${depositData.notes}`
        }
    }
    tooltip.querySelector('#tooltip-bottom').innerHTML = tooltipBottomHtml;
    if(e) {
        tooltip.style.top = e.clientY + 8 + 'px';
        tooltip.style.left = e.clientX + 10 + 'px';
    }
}

window.MAP_VIEWER.hideTooltip = function() {
    this.tooltip.style.visibility = 'hidden';
}

window.MAP_VIEWER.onTileSelected = function (tile, e, ignoreMouseDown = false) {
    if(e) {
        this.renderTooltip(tile, e);
    }

    if(this.editor.mode !== 'terrain' || (!this.editor.mouseDown && !ignoreMouseDown) || !this.editor.activeUid)
        return;

    tile.setAttribute('data-terrain', this.editor.activeUid);
    this.editor.terrainQueue.debounceCounter++;
    this.editor.terrainQueue.queue[tile.getAttribute('data-id')] = this.editor.activeUid;
    this.reRenderTiles();
    this.renderTooltip(tile, e);

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

window.MAP_VIEWER.loadDeposits = function () {
    for(const tile of this.layers.deposits.children) {
        const id = Number(tile.getAttribute('data-id'));
        if(id === 0)
            continue;

        const x = Number(tile.getAttribute('data-x'));
        const y = Number(tile.getAttribute('data-y'));
        const depositTypeUid = tile.getAttribute('data-deposit');
        const amount = Number(tile.getAttribute('data-amount'));
        const notes = tile.getAttribute('data-notes')

        this.editor.deposits[`${x}:${y}`] = {id, x, y, depositTypeUid, amount, notes};
    }
}

window.MAP_VIEWER.saveDeposit = async function() {
    const planetId = this.canvas.getAttribute('data-planet-id')
    const formData = new FormData(document.querySelector('#deposit-editor-modal form'));
    let depositId = Number(formData.get('id'))
    if(depositId === 0) {
        const requestData = {x: formData.get('x'), y: formData.get('y'), 'depositTypeUid': formData.get('deposit'), 'amount': formData.get('amount'), 'notes': formData.get('notes')};
        const createResponse = await fetch(`/api/data/planets/${planetId}/deposits`, {method: 'POST', body: JSON.stringify(requestData), headers: { 'Content-Type': 'application/json'}});
        const responseJson = await createResponse.json();
        depositId = responseJson.id;
    } else {
        const requestData = {'depositTypeUid': formData.get('deposit'), 'amount': formData.get('amount'), 'notes': formData.get('notes')};
        await fetch(`/api/data/planets/${planetId}/deposits/${depositId}`, {method: 'PUT', body: JSON.stringify(requestData),headers: { 'Content-Type': 'application/json'}});
    }

    const tile = this.layers.deposits.querySelector(`div[data-x="${formData.get('x')}"][data-y="${formData.get('y')}"]`);
    tile.setAttribute('data-deposit', formData.get('deposit'));
    tile.setAttribute('data-amount', formData.get('amount'));
    tile.setAttribute('data-notes', formData.get('notes'));
    tile.setAttribute('data-id', depositId);
    this.loadDeposits();
    this.reRenderTiles();
    $.modal.close();
}

window.MAP_VIEWER.deleteDeposit = async function() {
    const planetId = this.canvas.getAttribute('data-planet-id')
    const formData = new FormData(document.querySelector('#deposit-editor-modal form'));
    const depositId = Number(formData.get('id'));
    if(depositId === 0) {
        $.modal.close();
        return;
    }

    await fetch(`/api/data/planets/${planetId}/deposits/${depositId}`, {method: 'DELETE'});
    const tile = this.layers.deposits.querySelector(`div[data-x="${formData.get('x')}"][data-y="${formData.get('y')}"]`);
    tile.setAttribute('data-id', '0')
    tile.setAttribute('data-deposit', '');
    tile.setAttribute('data-amount', '0');
    tile.setAttribute('data-notes', '');
    const depositDataKey = `${formData.get('x')}:${formData.get('y')}`;
    delete this.editor.deposits[depositDataKey];
    this.reRenderTiles();
    $.modal.close();
}

window.MAP_VIEWER.showDepositEditor = function(tile) {
    const x = tile.getAttribute('data-x');
    const y = tile.getAttribute('data-y')
    const key = `${x}:${y}`;
    const depositData = this.editor.deposits[key];

    const depositEditor = document.getElementById('deposit-editor-modal');
    depositEditor.querySelector('.title').innerText = depositData
            ? `Deposit at ${x}, ${y}`
            : `New deposit at ${x}, ${y}`;

    depositEditor.querySelector('input[name="id"]').value = depositData?.id ?? 0;
    depositEditor.querySelector('select[name="deposit"]').value = depositData?.depositTypeUid ?? '16:1';
    depositEditor.querySelector('input[name="amount"]').value = depositData?.amount ?? 0;
    depositEditor.querySelector('textarea[name="notes"]').value = depositData?.notes ?? '';
    depositEditor.querySelector('input[name="x"]').value = x;
    depositEditor.querySelector('input[name="y"]').value = y;

    if(!depositData) {
        depositEditor.querySelector('#modal__delete').style.display = 'none';
    } else {
        depositEditor.querySelector('#modal__delete').style.display = '';
    }

    $(depositEditor).find('select').select2();

    $(depositEditor).modal();
}

window.MAP_VIEWER.onTileClicked = function(tile) {
    if(this.editor.mode !== 'deposits' || tile.classList.contains('map__tile') === false)
        return;

    this.showDepositEditor(tile);
}

async function hydrate() {
    window.MAP_VIEWER.data.terrain = await fetchTerrainTypes();
    window.MAP_VIEWER.data.deposits = await fetchDepositTypes();

    const map = document.getElementById('map');
    window.MAP_VIEWER.map = map;
    window.MAP_VIEWER.canvas = map.querySelector('.map__canvas');
    window.MAP_VIEWER.layers.terrain = map.querySelector('.map__terrain-layer');
    window.MAP_VIEWER.layers.deposits = map.querySelector('.map__deposits-layer');

    const toolbar = document.getElementById('toolbar');
    window.MAP_VIEWER.toolbar.modes = toolbar.querySelector('.toolbar__modes');
    window.MAP_VIEWER.toolbar.terrain = toolbar.querySelector('.toolbar__terrain');
    window.MAP_VIEWER.toolbar.deposits = toolbar.querySelector('.toolbar__deposits');

    const tooltip = document.getElementById('map-tooltip');
    window.MAP_VIEWER.tooltip = tooltip;

    window.MAP_VIEWER.loadDeposits();
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
            window.MAP_VIEWER.onTileSelected(e.target, undefined, true);
        }
    });
    window.addEventListener('mouseup', () => window.MAP_VIEWER.editor.mouseDown = false);
    map.querySelectorAll('.map__tile').forEach(tile => {
        tile.addEventListener('mousemove', (e) => {
            window.MAP_VIEWER.onTileSelected(tile, e);
        });
        tile.addEventListener('click', (e) => window.MAP_VIEWER.onTileClicked(tile));
    })
    window.MAP_VIEWER.canvas.addEventListener('mousemove', (e) => {
        if(e.target.classList.contains('map__tile') == false)
            window.MAP_VIEWER.hideTooltip();
    })
    window.MAP_VIEWER.canvas.addEventListener('mouseleave', () => window.MAP_VIEWER.hideTooltip());

    document.getElementById('modal__close').addEventListener('click', () => $.modal.close());
    document.getElementById('modal__delete').addEventListener('click', () => window.MAP_VIEWER.deleteDeposit());
    document.querySelector('#deposit-editor-modal form').addEventListener('submit', (e) => {
        e.preventDefault();
        window.MAP_VIEWER.saveDeposit();
    })
}

async function fetchTerrainTypes() {
    const response = await fetch(`/api/definitions/terrain`);
    const responseJson = await response.json();

    return responseJson.reduce((map, item) => (
        {...map, [item.uid]: {src: item.img.src, name: item.name}}
    ), {})
}

async function fetchDepositTypes() {
    const response = await fetch(`/api/definitions/deposits`);
    const responseJson = await response.json();

    return responseJson.reduce((map, item) => (
        {...map, [item.uid]: {src: item.img.src, name: item.name}}
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