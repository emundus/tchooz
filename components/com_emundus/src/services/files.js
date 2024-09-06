import { FetchClient } from './fetchClient.js'

const client = new FetchClient('file')

export default {
    // eslint-disable-next-line no-unused-vars
    async getFiles(type = 'default', refresh = false, limit = 25, page = 0) {
        try {
            return await client.get('getfiles', {
                type: type,
                refresh: refresh,
            })
        } catch (e) {
            return false
        }
    },

    async getColumns(type = 'default') {
        try {
            return await client.get('getcolumns', {
                type: type,
            })
        } catch (e) {
            return false
        }
    },

    async getEvaluationFormByFnum(fnum, type) {
        try {
            return await client.get('getevaluationformbyfnum', {
                fnum: fnum,
                type: type,
            })
        } catch (e) {
            return false
        }
    },

    async getMyEvaluation(fnum) {
        try {
            return await client.get('getmyevaluation', {
                fnum: fnum,
            })
        } catch (e) {
            return false
        }
    },

    async checkAccess(fnum) {
        try {
            return await client.get('checkaccess', {
                fnum: fnum,
            })
        } catch (e) {
            return false
        }
    },

    async getLimit(type = 'default') {
        try {
            return await client.get('getlimit', {
                type: type,
            })
        } catch (e) {
            return false
        }
    },

    async getPage(type = 'default') {
        try {
            return await client.get('getpage', {
                type: type,
            })
        } catch (e) {
            return false
        }
    },

    async updateLimit(limit) {
        try {
            return await client.get('updatelimit', {
                limit: limit,
            })
        } catch (e) {
            return false
        }
    },

    async updatePage(page) {
        try {
            return await client.get('updatepage', {
                page: page,
            })
        } catch (e) {
            return false
        }
    },

    async getSelectedTab(type) {
        try {
            return await client.get('getselectedtab', {
                type: type,
            })
        } catch (e) {
            return false
        }
    },

    async setSelectedTab(tab, type = 'evaluation') {
        try {
            return await client.get('setselectedtab', {
                tab: tab,
                type: type,
            })
        } catch (e) {
            return false
        }
    },

    async getFile(fnum, type = 'default') {
        try {
            return await client.get('getfile', {
                fnum: fnum,
                type: type,
            })
        } catch (e) {
            return false
        }
    },

    async getFilters() {
        try {
            return await client.get('getfilters')
        } catch (e) {
            return false
        }
    },

    async applyFilters(filters) {
        const data = {
            filters: JSON.stringify(filters),
        }

        try {
            return await client.post('applyfilters', data)
        } catch (e) {
            return false
        }
    },
}
