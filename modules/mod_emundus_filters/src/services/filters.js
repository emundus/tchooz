import {FetchClient} from './fetchClient.js';
const client = new FetchClient('files');

export default {
    async applyFilters(filters, search_filters, successEvent) {
        let applied = false;

        if (filters) {
            filters = JSON.parse(JSON.stringify(filters));
            filters = filters.map(filter => {
                delete filter.values;
                return filter;
            });

            return client.post('applyfilters', {
                filters: JSON.stringify(filters),
                search_filters: JSON.stringify(search_filters)
            }).then(data => {
                if (data.status) {
                    applied = true;
                    window.dispatchEvent(successEvent);
                    return applied;
                }
            });
        } else {
            return applied;
        }
    },
    async saveFilters(filters, name, moduleId) {
        let saved = false;

        if (filters && name.length > 0) {
            return client.post('newsavefilters', {
                filters:  JSON.stringify(filters),
                name: name,
                item_id: moduleId
            }).then(data => {
               if (data.status) {
                   saved = true;
               }

                return saved;
            }).catch((error) => {
                console.log(error);
                return saved;
            });
        } else {
            return saved;
        }
    },
    async updateFilter(filters, moduleId, filterId) {
        let updated = false;

        if (filters) {
            client.post('updatefilter', {
                filters:  JSON.stringify(filters),
                item_id: moduleId,
                id: filterId
            }).then(data => {
               if (data.status) {
                   updated = true;
               }

                return updated;
            });
        } else {
            return updated;
        }
    },
    renameFilter(filterId, name) {
        let renamed = false;

        if (filterId && name.length > 0) {
            return client.post('renamefilter', {
                id: filterId,
                name: name
            }).then(data => {
                if (data.status) {
                    renamed = true;
                }

                return renamed;
            });
        } else {
            return renamed;
        }
    },
    async deleteFilter(filterId) {
        let deleted = false;

        if (filterId) {
            client.post('deletefilters', {id: filterId}).then(data => {
               if (data.status) {
                   deleted = true;
               }

                return deleted;
            });
        } else {
            return deleted;
        }
    },
    async getRegisteredFilters(moduleId) {
        let filters = [];

        return client.get('getsavedfilters', {item_id: moduleId}).then(data => {
            if (data.status) {
                filters = data.data;
            }

            return filters;
        }).catch(error => {
            console.log(error);
            return filters;
        });
    },
    async countFiltersValues(moduleId, menuId) {
        return client.post('setFiltersValuesAvailability', {
            module_id: moduleId,
            menu_id: menuId
        }).then(data => {
            if (data.status) {
                return data;
            }
        }).catch((error) => {
            console.log(error);
            return {
                status: false,
                message: 'Error'
            };
        });
    },
    async getFilterValues(filterId) {
        let values = [];

        return client.get('getfiltervalues', {id: filterId}).then(data => {
            if (data.status) {
                values = data.data;
            }

            return values;
        }).catch(error => {
            console.log(error);
            return values;
        });
    },
    async getFiltersAvailable(moduleId) {
        let filters = [];

        if (moduleId > 0) {
            return client.get('getFiltersAvailable', {
                module_id: moduleId
            }).then(data => {
                if (data.status) {
                    filters = data.data;
                }

                return filters;
            }).catch(error => {
                throw new Error('Error occured while getting filters : ' . error.message);
            });
        } else {
            throw new Error('Module id is not valid');
        }
    },
    async shareFilter(filter, users, groups){
        if (filter > 0) {
            return client.post('sharefilter', {
                filter: filter,
                users: users,
                groups: groups
            }).then(data => {
                return data;
            }).catch((error) => {
                throw new Error('Error occured while sharing filter : ' . error.message);
            });
        } else {
            throw new Error('Filter id is not valid');
        }
    },
    async getAlreadySharedTo(filterId) {
        let alreadySharedTo = {
            'users': [],
            'groups': []
        };

        if (filterId > 0) {
            return client.get('getalreadysharedto', {
                filter_id: filterId
            }).then(data => {
                if (data.status) {
                    alreadySharedTo = data.data;
                }

                return alreadySharedTo;
            }).catch((error) => {
               return alreadySharedTo;
            });
        } else {
            return alreadySharedTo;
        }
    },
    async deleteSharing(filterId, id, type) {
        if (filterId > 0) {
            return client.post('deletesharing', {
                filter_id: filterId,
                id: id,
                type: type
            }).then(data => {
                if (data.status) {
                    return data;
                }
            }).catch((error) => {
                throw new Error('Error occured while deleting sharing : ' . error.message);
            });
        } else {
            throw new Error('Filter id is not valid');
        }
    },
    async toggleFilterFavoriteState(filterId, setFavorite) {
        if (filterId > 0) {
            return client.post('togglefilterfavorite', {
                filter_id: filterId,
                set_favorite: setFavorite
            }).then(data => {
                if (data.status) {
                    return data;
                }
            }).catch((error) => {
                throw new Error('Error occured while toggling favorite state : ' . error.message);
            });
        }
    },
    async defineAsDefaultFilter(filterId) {
        if (filterId > 0) {
            return client.post('defineasdefaultfilter', {
                filter_id: filterId
            }).then(data => {
                if (data.status) {
                    return data;
                }
            }).catch((error) => {
                throw new Error('Error occured while defining as default filter : ' . error.message);
            });
        }
    }
};